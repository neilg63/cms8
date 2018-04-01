<?php

namespace Drupal\double_field\Tests;

use Drupal\Component\Utility\NestedArray;
use Drupal\double_field\Plugin\Field\FieldFormatter\Base as BaseFormatter;
use Drupal\double_field\Plugin\Field\FieldWidget\DoubleField;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of text fields.
 */
abstract class TestBase extends WebTestBase {

  /**
   * Field cardinality.
   */
  const CARDINALITY = 5;

  /**
   * A user with relevant administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A content type id.
   *
   * @var string
   */
  protected $contentTypeId;

  /**
   * A field name used for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * A path to field settings form.
   *
   * @var string
   */
  protected $fieldAdminPath;

  /**
   * A path to form display settings form.
   *
   * @var string
   */
  protected $formDisplayAdminPath;

  /**
   * A path to display settings form.
   *
   * @var string
   */
  protected $displayAdminPath;

  /**
   * A path to field storage settings form.
   *
   * @var string
   */
  protected $fieldStorageAdminPath;

  /**
   * A path to content type settings form.
   *
   * @var string
   */
  protected $contentTypeAdminPath;

  /**
   * A path to node add form.
   *
   * @var string
   */
  protected $nodeAddPath;

  /**
   * Field storage instance.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * Field instance.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $field;

  /**
   * The values of the field.
   *
   * @var array
   */
  protected $values;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'double_field',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->contentTypeId = $this->drupalCreateContentType(['type' => $this->randomMachineName()])->id();
    $this->fieldName = strtolower($this->randomMachineName());
    $this->contentTypeAdminPath = 'admin/structure/types/manage/' . $this->contentTypeId;
    $this->fieldAdminPath = "{$this->contentTypeAdminPath}/fields/node.{$this->contentTypeId}.{$this->fieldName}";
    $this->fieldStorageAdminPath = $this->fieldAdminPath . '/storage';
    $this->formDisplayAdminPath = $this->contentTypeAdminPath . '/form-display';
    $this->displayAdminPath = $this->contentTypeAdminPath . '/display';
    $this->nodeAddPath = 'node/add/' . $this->contentTypeId;

    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer nodes',
      'administer node form display',
      'administer node display',
      "delete any {$this->contentTypeId} content",
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a field storage for testing.
    $storage_settings['storage'] = [
      'first' => [
        'type' => 'string',
        'maxlength' => 50,
        'precision' => 10,
        'scale' => 2,
        'datetime_type' => 'datetime',
      ],
      'second' => [
        'type' => 'string',
        'maxlength' => 50,
        'precision' => 10,
        'scale' => 2,
        'datetime_type' => 'datetime',
      ],
    ];

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'double_field',
      'settings' => $storage_settings,
    ]);
    $this->fieldStorage->save();

    // Create a field storage for testing.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->contentTypeId,
      'required' => TRUE,
    ]);
    $this->field->save();

    $this->saveWidgetSettings([]);
    $this->saveFormatterSettings('unformatted_list');

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $this->values[$delta] = [
        'first' => $this->randomMachineName(),
        'second' => $this->randomMachineName(),
      ];
    }
  }

  /**
   * Finds Drupal messages on the page.
   *
   * @param string $type
   *   A message type (e.g. status, warning, error).
   *
   * @return array
   *   List of found messages.
   */
  protected function getMessages($type) {
    $messages = [];

    $xpath = '//div[@aria-label="' . ucfirst($type) . ' message"]';
    // Error messages have one more wrapper.
    if ($type == 'error') {
      $xpath .= '/div[@role="alert"]';
    }
    $wrapper = $this->xpath($xpath);
    if (!empty($wrapper[0])) {
      // Multiple messages are rendered with an HTML list.
      if (isset($wrapper[0]->ul)) {
        foreach ($wrapper[0]->ul->li as $li) {
          /* @var \SimpleXMLElement $li */
          $messages[] = trim(strip_tags($li->asXML()));
        }
      }
      else {
        unset($wrapper[0]->h2);
        $messages[] = trim(preg_replace('/\s+/', ' ', strip_tags($wrapper[0]->asXML())));
      }
    }

    return $messages;
  }

  /**
   * Passes if a given error message was found on the page.
   */
  protected function assertErrorMessage($message) {
    $messages = $this->getMessages('error');
    $this->assertTrue(in_array($message, $messages), 'Error message was found.');
  }

  /**
   * Passes if a given warning message was found on the page.
   */
  protected function assertWarningMessage($message) {
    $messages = $this->getMessages('warning');
    $this->assertTrue(in_array($message, $messages), 'Warning message was found.');
  }

  /**
   * Passes if a given status message was found on the page.
   */
  protected function assertStatusMessage($message) {
    $messages = $this->getMessages('status');
    $this->assertTrue(in_array($message, $messages), 'Status message was found.');
  }

  /**
   * Passes if no error messages were found on the page.
   */
  protected function assertNoErrorMessages() {
    $messages = $this->getMessages('error');
    $this->assertTrue(count($messages) == 0, 'No error messages were found.');
  }

  /**
   * Passes if all given attributes matches expectations.
   */
  protected function assertAttributes($attributes, $expected_attributes) {
    foreach ($expected_attributes as $expected_attribute => $value) {
      $expression = isset($attributes[$expected_attribute]) && $attributes[$expected_attribute] == $value;
      $this->assertTrue($expression, sprintf('Valid attribute "%s" was found', $expected_attribute));
    }
  }

  /**
   * Deletes all nodes.
   */
  protected function deleteNodes() {
    /* @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = Node::loadMultiple();
    foreach ($nodes as $node) {
      // Node::delete() does not work here as expected by some reason.
      $this->drupalPostForm(sprintf('node/%d/delete', $node->id()), [], t('Delete'));
    }
  }

  /**
   * Saves field settings.
   */
  protected function saveFieldSettings(array $settings) {
    $persisted_settings = $this->field->getSettings();
    // Override allowed values instead of merging.
    foreach (['first', 'second'] as $subfield) {
      if (isset($persisted_settings[$subfield]['allowed_values'], $settings[$subfield]['allowed_values'])) {
        unset($persisted_settings[$subfield]['allowed_values']);
      }
    }
    $this->field->setSettings(
      NestedArray::mergeDeep($persisted_settings, $settings)
    );
    $this->field->save();
  }

  /**
   * Saves storage settings.
   */
  protected function saveFieldStorageSettings(array $settings) {
    $this->fieldStorage->setSettings(
      NestedArray::mergeDeep($this->fieldStorage->getSettings(), $settings)
    );
    $this->fieldStorage->save();
  }

  /**
   * Saves widget settings.
   */
  protected function saveWidgetSettings(array $settings) {
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_display */
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $this->contentTypeId . '.default');

    $options = [
      'type' => 'double_field',
      'weight' => 100,
      'settings' => NestedArray::mergeDeep(DoubleField::defaultSettings(), $settings),
      'third_party_settings' => [],
    ];

    $form_display->setComponent($this->fieldName, $options);
    $form_display->save();
  }

  /**
   * Saves formatter settings.
   */
  protected function saveFormatterSettings($formatter, array $settings = []) {

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->contentTypeId}.default");

    $options = [
      'label' => 'hidden',
      'type' => 'double_field_' . $formatter,
      'weight' => 100,
      'settings' => NestedArray::mergeDeep(BaseFormatter::defaultSettings(), $settings),
      'third_party_settings' => [],
    ];

    $view_display->setComponent($this->fieldName, $options);
    $view_display->save();
  }

  /**
   * Returns formatter options.
   */
  protected function getFormatterOptions() {
    /* @var \Drupal\Core\Entity\Display\EntityDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->contentTypeId}.default");
    return $view_display->getComponent($this->fieldName);
  }

  /**
   * Passes if two arrays are identical.
   *
   * @param array $array1
   *   The first array to check.
   * @param array $array2
   *   The second array to check.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertIdenticalArray(array $array1, array $array2, $message = '') {
    $identical = TRUE;
    foreach ($array1 as $key => $value) {
      $identical = $identical && isset($array2[$key]) && $array2[$key] == $value;
    }
    return $this->assertTrue($identical, $message);
  }

  /**
   * Passes if all given xpath axes are valid.
   */
  protected function assertAxes(array $axes) {
    foreach ($axes as $axis) {
      $this->assertEqual(count($this->xpath($axis)), 1, t('Found valid xpath: %xpath', ['%xpath' => $axis]));
    }
  }

}
