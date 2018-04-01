<?php

namespace Drupal\double_field\Tests;

/**
 * Tests double field formatters.
 *
 * @group double_field
 */
class FormatterTest extends TestBase {

  /**
   * General settings values to submit.
   *
   * @var array
   */
  protected $generalSettingsEdit = [];

  /**
   * General expected items.
   *
   * These summary items are expected to exist for all formatters.
   *
   * @var array
   */
  protected $generalExpectedItems = [];

  /**
   * Test formatter output.
   */
  public function testFormatterOutput() {

    $this->fieldStorage->setCardinality(self::CARDINALITY);
    $this->fieldStorage->save();

    // Create a node for testing.
    $edit = ['title[0][value]' => $this->randomMachineName()];
    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      foreach (['first', 'second'] as $subfield) {
        $edit[$this->fieldName . "[$delta][$subfield]"] = $this->values[$delta][$subfield];
      }
    }
    $this->drupalPostForm($this->nodeAddPath, $edit, t('Save'));

    $settings = [];
    foreach (['first', 'second'] as $subfield) {
      $settings[$subfield] = [
        'prefix' => $this->randomMachineName(),
        'suffix' => $this->randomMachineName(),
      ];
    }

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $node_path = 'node/' . $node->id();

    // -- Accordion.
    $this->saveFormatterSettings('accordion', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//div[@class='double-field-accordion']/h3)[$index]/a",
        "(//div[@class='double-field-accordion']/div)[$index]",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    // -- Tabs.
    $this->saveFormatterSettings('tabs', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//div[@class='double-field-tabs']/ul/li)[$index]/a[@href='#double-field-tab-$delta']",
        "(//div[@class='double-field-tabs']/div[@id='double-field-tab-$delta'])",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    // -- Table.
    $settings['number_column'] = TRUE;
    $settings['number_column_label'] = $this->randomMachineName();
    $settings['first_column_label'] = $this->randomMachineName();
    $settings['second_column_label'] = $this->randomMachineName();
    $this->saveFormatterSettings('table', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//table[contains(@class, 'double-field-table')]/tbody/tr)[$index]/td[2]",
        "(//table[contains(@class, 'double-field-table')]/tbody/tr)[$index]/td[3]",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    $table_header = $this->xpath('//table[contains(@class, "double-field-table")]/thead/tr');
    $this->assertEqual($settings['number_column_label'], (string) $table_header[0]->th[0], 'Number column label was found.');
    $this->assertEqual($settings['first_column_label'], (string) $table_header[0]->th[1], 'First column label was found.');
    $this->assertEqual($settings['second_column_label'], (string) $table_header[0]->th[2], 'Second column label was found.');

    // Make sure table header disappears if labels are not specified.
    $settings['first_column_label'] = '';
    $settings['second_column_label'] = '';
    $this->saveFormatterSettings('table', $settings);
    $this->drupalGet($node_path);

    $table_header = $this->xpath('//table[contains(@class, "double-field-table")]/thead');
    $this->assertFalse(isset($table_header[0]), 'Table header is not shown.');

    // Test 'hidden' option.
    $settings['first']['hidden'] = TRUE;
    $this->saveFormatterSettings('table', $settings);
    $this->drupalGet($node_path);

    $element = $this->xpath('(//table[contains(@class, "double-field-table")]/tbody/tr)[1]/td[2]/text()');
    $this->assertFalse(isset($element[0]), 'First item was not found.');

    $element = $this->xpath('(//table[contains(@class, "double-field-table")]/tbody/tr)[1]/td[3]/text()');
    $this->assertTrue(isset($element[0]), 'Second item was found.');
    $settings['first']['hidden'] = FALSE;

    // -- Details.
    $this->saveFormatterSettings('details', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      // Details prefix and suffix are not wrapped. So we will check them
      // individually.
      $summary = $this->xpath("(//details[contains(@class, 'double-field-details') and @open])[$index]/summary");
      $first_value = $settings['first']['prefix'] . $this->values[$delta]['first'] . $settings['first']['suffix'];
      $this->assertEqual((string) $summary[0], $first_value, 'Valid summary was found.');

      $details_wrapper = $this->xpath("(//details[contains(@class, 'double-field-details')])[$index]/div[@class='details-wrapper']");
      $second_value = $settings['second']['prefix'] . $this->values[$delta]['second'] . $settings['second']['suffix'];
      $this->assertEqual(trim($details_wrapper[0]), $second_value, 'Valid details content was found.');
    }

    $settings['open'] = FALSE;
    $this->saveFormatterSettings('details', $settings);
    $this->drupalGet($node_path);
    $summary = $this->xpath("//details[contains(@class, 'double-field-details')]")[0];

    $this->assertFalse(isset($summary->attributes()['open']), 'Details element is not open.');

    // -- HTML list.
    $this->saveFormatterSettings('html_list', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//ul[@class='double-field-list']/li[@class='container-inline']/div[@class='double-field-first'])[$index]",
        "(//ul[@class='double-field-list']/li[@class='container-inline']/div[@class='double-field-second'])[$index]",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    $settings['list_type'] = 'ol';
    $this->saveFormatterSettings('html_list', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//ol[@class='double-field-list']/li[@class='container-inline']/div[@class='double-field-first'])[$index]",
        "(//ol[@class='double-field-list']/li[@class='container-inline']/div[@class='double-field-second'])[$index]",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    // Disable 'inline' option and check if the "container-inline" class has
    // been removed.
    $settings['inline'] = FALSE;
    $this->saveFormatterSettings('html_list', $settings);
    $this->drupalGet($node_path);

    $li_element = $this->xpath('//ol[@class="double-field-list"]/li')[0];
    $this->assertFalse(isset($li_element->attributes()['class']), '"container-inline" class is not found.');

    // -- Definition list.
    $settings['list_type'] = 'dl';
    $this->saveFormatterSettings('html_list', $settings);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//dl[@class='double-field-definition-list']/dt)[$index]",
        "(//dl[@class='double-field-definition-list']/dd)[$index]",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    // -- Unformatted list.
    $settings['inline'] = TRUE;
    $this->saveFormatterSettings('unformatted_list', $settings);
    $this->drupalGet($this->displayAdminPath);
    $this->drupalGet($node_path);

    for ($delta = 0; $delta < self::CARDINALITY; $delta++) {
      $index = $delta + 1;
      $axes = [
        "(//div[contains(@class, 'double-field-unformatted-list')]/div[contains(@class, 'container-inline')])[$index]/div[@class='double-field-first']",
        "(//div[contains(@class, 'double-field-unformatted-list')]/div[contains(@class, 'container-inline')])[$index]/div[@class='double-field-second']",
      ];
      $this->assertFieldValues($axes, $delta);
    }

    // Disable 'inline' option and check if the "container-inline" class has
    // been removed.
    $settings['inline'] = FALSE;
    $this->saveFormatterSettings('unformatted_list', $settings);
    $this->drupalGet($node_path);

    $element = $this->xpath('//div[contains(@class, "double-field-unformatted-list")]/div[contains(@class, "container-inline")]');
    $this->assertEqual(count($element), 0, '"container-inline" class is not found.');

    // Test 'hidden' option.
    $settings['first']['hidden'] = TRUE;
    $this->saveFormatterSettings('unformatted_list', $settings);
    $this->drupalGet($node_path);

    $element = $this->xpath('(//div[contains(@class, "double-field-unformatted-list")]/div)[1]/div[@class="double-field-first"]');
    $this->assertFalse(isset($element[0]), 'First item was not found.');

    $element = $this->xpath('(//div[contains(@class, "double-field-unformatted-list")]/div)[1]/div[@class="double-field-second"]');
    $this->assertTrue(isset($element[0]), 'Second item was found.');

    $this->deleteNodes();

    // Test 'link' option.
    $storage_settings['storage']['first']['type'] = 'email';
    $storage_settings['storage']['second']['type'] = 'telephone';
    $this->saveFieldStorageSettings($storage_settings);
    $settings = [
      'first' => [
        'link' => TRUE,
      ],
      'second' => [
        'link' => TRUE,
      ],
    ];
    $this->saveFormatterSettings('unformatted_list', $settings);

    // Create a node for testing.
    $edit = ['title[0][value]' => $this->randomMachineName()];
    $edit[$this->fieldName . "[0][first]"] = 'abc@example.com';
    $edit[$this->fieldName . "[0][second]"] = '123456789';

    $this->drupalPostForm($this->nodeAddPath, $edit, t('Save'));
    $axes = [];
    $axes[] = '(//div[contains(@class, "double-field-unformatted-list")]/div)[1]/div[@class="double-field-first"]/a[@href="mailto:abc@example.com" and text()="abc@example.com"]';
    $axes[] = '(//div[contains(@class, "double-field-unformatted-list")]/div)[1]/div[@class="double-field-second"]/a[@href="tel:123456789" and text()="123456789"]';
    $this->assertAxes($axes);
    $this->deleteNodes();

    // Test 'date format' option.
    $storage_settings['storage']['first']['type'] = 'datetime_iso8601';
    $storage_settings['storage']['second']['type'] = 'datetime_iso8601';
    $storage_settings['storage']['second']['datetime_type'] = 'date';
    $this->saveFieldStorageSettings($storage_settings);
    $settings = [
      'first' => [
        'format_type' => 'short',
      ],
      'second' => [
        'format_type' => 'long',
      ],
    ];
    $this->saveFormatterSettings('unformatted_list', $settings);
    $this->drupalGet($this->nodeAddPath);

    $edit = ['title[0][value]' => $this->randomMachineName()];
    $edit[$this->fieldName . "[0][first][date]"] = '2017-10-11';
    $edit[$this->fieldName . "[0][first][time]"] = '01:15:59';
    $edit[$this->fieldName . "[0][second][date]"] = '2011-12-11';
    $this->drupalPostForm($this->nodeAddPath, $edit, t('Save'));
    $dates = $this->xpath('(//div[contains(@class, "double-field-unformatted-list")]/div)[1]//time');
    $iso_pattern = '#^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$#';
    $this->assertTrue(preg_match($iso_pattern, $dates[0]->attributes()['datetime']));
    $this->assertTrue(preg_match('#^\d{2}/\d{2}/\d{4} - \d{2}:\d{2}$#', $dates[0]));
    $this->assertTrue(preg_match($iso_pattern, $dates[1]->attributes()['datetime']));
    $this->assertTrue(preg_match('#^[A-Z][a-z]+, [A-Z][a-z]+ \d{2}, \d{4} - \d{2}:\d{2}$#', $dates[1]));
  }

  /**
   * Test output of boolean field.
   */
  public function testBooleanLabels() {

    $storage_settings['storage']['first']['type'] = 'boolean';
    $storage_settings['storage']['second']['type'] = 'boolean';
    $this->saveFieldStorageSettings($storage_settings);

    $field_settings['first']['on_label'] = $this->randomMachineName();
    $field_settings['second']['off_label'] = $this->randomMachineName();
    $field_settings['second']['required'] = FALSE;
    $this->saveFieldSettings($field_settings);

    $widget_settings['first']['type'] = 'checkbox';
    $widget_settings['second']['type'] = 'checkbox';
    $this->saveWidgetSettings($widget_settings);

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      $this->fieldName . "[0][first]" => TRUE,
      $this->fieldName . "[0][second]" => FALSE,
    ];
    $this->drupalPostForm($this->nodeAddPath, $edit, t('Save'));

    $this->values[0] = [
      'first' => $field_settings['first']['on_label'],
      'second' => $field_settings['second']['off_label'],
    ];
    $axes = [
      "//div[contains(@class, 'double-field-unformatted-list')]//div[@class='double-field-first']",
      "//div[contains(@class, 'double-field-unformatted-list')]//div[@class='double-field-second']",
    ];
    $this->assertFieldValues($axes);
  }

  /**
   * Test output of list labels.
   */
  public function testListLabels() {

    $storage_settings['storage']['first']['type'] = 'string';
    $storage_settings['storage']['second']['type'] = 'string';
    $this->saveFieldStorageSettings($storage_settings);

    $field_settings['first']['list'] = TRUE;
    $field_settings['first']['allowed_values'] = [
      $this->randomMachineName() => $this->randomMachineName(),
      $this->randomMachineName() => $this->randomMachineName(),
      $this->randomMachineName() => $this->randomMachineName(),
    ];
    $field_settings['second']['list'] = TRUE;
    $field_settings['second']['allowed_values'] = [
      mt_rand(1, 100) => $this->randomMachineName(),
      mt_rand(1, 100) => $this->randomMachineName(),
      mt_rand(1, 100) => $this->randomMachineName(),
    ];
    $this->saveFieldSettings($field_settings);

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      $this->fieldName . "[0][first]" => array_rand($field_settings['first']['allowed_values']),
      $this->fieldName . "[0][second]" => array_rand($field_settings['second']['allowed_values']),
    ];
    $this->drupalPostForm($this->nodeAddPath, $edit, t('Save'));

    $this->values[0] = [
      'first' => $field_settings['first']['allowed_values'][$edit[$this->fieldName . "[0][first]"]],
      'second' => $field_settings['second']['allowed_values'][$edit[$this->fieldName . "[0][second]"]],
    ];
    $axes = [
      "//div[contains(@class, 'double-field-unformatted-list')]//div[@class='double-field-first']",
      "//div[contains(@class, 'double-field-unformatted-list')]//div[@class='double-field-second']",
    ];
    $this->assertFieldValues($axes);
  }

  /**
   * Test formatter settings form.
   */
  public function testFormatterSettingsForm() {

    $name_prefix = "fields[{$this->fieldName}][settings_edit_form][settings]";

    $general_axes = [];
    $settings = [];
    foreach (['first', 'second'] as $subfield) {
      $settings[$subfield] = [
        'hidden' => (bool) mt_rand(0, 1),
        'prefix' => $this->randomMachineName(),
        'suffix' => $this->randomMachineName(),
      ];

      $hidden = $settings[$subfield]['hidden'] ? '@checked' : 'not(@checked)';
      $general_axes[] = "//input[@name='{$name_prefix}[{$subfield}][hidden]' and $hidden]";
      $general_axes[] = "//input[@name='{$name_prefix}[{$subfield}][prefix]' and @value='{$settings[$subfield]['prefix']}']";
      $general_axes[] = "//input[@name='{$name_prefix}[{$subfield}][suffix]' and @value='{$settings[$subfield]['suffix']}']";

      $this->generalSettingsEdit["{$name_prefix}[{$subfield}][hidden]"] = (bool) mt_rand(0, 1);
      $this->generalSettingsEdit["{$name_prefix}[{$subfield}][prefix]"] = $this->randomMachineName();
      $this->generalSettingsEdit["{$name_prefix}[{$subfield}][suffix]"] = $this->randomMachineName();

      $this->generalExpectedItems[] = '<b>' . ucfirst($subfield) . ' subfield - text</b>';
      $hidden = $this->generalSettingsEdit["{$name_prefix}[$subfield][hidden]"] ? 'yes' : 'no';
      $this->generalExpectedItems[] = t('Hidden: %hidden', ['%hidden' => $hidden]);
      $this->generalExpectedItems[] = t('Prefix: %prefix', ['%prefix' => $this->generalSettingsEdit["{$name_prefix}[$subfield][prefix]"]]);
      $this->generalExpectedItems[] = t('Suffix: %suffix', ['%suffix' => $this->generalSettingsEdit["{$name_prefix}[$subfield][suffix]"]]);
    }

    // -- Accordion.
    $this->saveFormatterSettings('accordion', $settings);

    // Click on the settings button to open the formatter settings form.
    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');
    $this->assertAxes($general_axes);

    $this->assertSummary();

    // -- Tabs.
    $this->saveFormatterSettings('accordion', $settings);

    // Click on the settings button to open the formatter settings form.
    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');
    $this->assertAxes($general_axes);

    $this->assertSummary();

    // -- Table.
    $settings['number_column'] = (bool) mt_rand(0, 1);
    $settings['number_column_label'] = $this->randomMachineName();
    $settings['first_column_label'] = $this->randomMachineName();
    $settings['second_column_label'] = $this->randomMachineName();
    $this->saveFormatterSettings('table', $settings);

    // Click on the settings button to open the formatter settings form.
    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');

    $axes = $general_axes;
    $number_column = $settings['number_column'] ? '@checked' : 'not(@checked)';
    $axes[] = "//input[@name='{$name_prefix}[number_column]' and {$number_column}]";
    $axes[] = "//input[@name='{$name_prefix}[number_column_label]' and @value='{$settings['number_column_label']}']";
    $axes[] = "//input[@name='{$name_prefix}[first_column_label]' and @value='{$settings['first_column_label']}']";
    $axes[] = "//input[@name='{$name_prefix}[second_column_label]' and @value='{$settings['second_column_label']}']";
    $this->assertAxes($axes);

    $edit = [
      "{$name_prefix}[number_column]" => (bool) mt_rand(0, 1),
      "{$name_prefix}[number_column_label]" => $this->randomMachineName(),
      "{$name_prefix}[first_column_label]" => $this->randomMachineName(),
      "{$name_prefix}[second_column_label]" => $this->randomMachineName(),
    ];

    $expected_items = [];
    $number_column = $edit["{$name_prefix}[number_column]"] ? 'yes' : 'no';
    $expected_items[] = t('Enable row number column: %number_column', ['%number_column' => $number_column]);
    if ($edit["{$name_prefix}[number_column]"]) {
      $expected_items[] = t('Number column label: %number_column_label', ['%number_column_label' => $edit["{$name_prefix}[number_column_label]"]]);
    }
    $expected_items[] = t('First column label: %first_column_label', ['%first_column_label' => $edit["{$name_prefix}[first_column_label]"]]);
    $expected_items[] = t('Second column label: %second_column_label', ['%second_column_label' => $edit["{$name_prefix}[second_column_label]"]]);
    $this->assertSummary($edit, $expected_items);

    // -- Details.
    $settings['open'] = (bool) mt_rand(0, 1);
    $this->saveFormatterSettings('details', $settings);

    $axes = $general_axes;
    $open = $settings['open'] ? '@checked' : 'not(@checked)';
    $axes[] = "//input[@name='{$name_prefix}[open]' and {$open}]";

    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');
    $this->assertAxes($axes);

    $edit = ["{$name_prefix}[open]" => (bool) mt_rand(0, 1)];

    $open = $edit["{$name_prefix}[open]"] ? 'yes' : 'no';
    $expected_items = [t('Open: %open', ['%open' => $open])];
    $this->assertSummary($edit, $expected_items);

    // -- HTML list.
    $list_types = [
      'ul' => t('Unordered list'),
      'ol' => t('Ordered list'),
      'dl' => t('Definition list'),
    ];
    $settings['list_type'] = array_rand($list_types);
    $settings['inline'] = TRUE;
    $this->saveFormatterSettings('html_list', $settings);

    $axes = $general_axes;
    $axes[] = "//input[@name='{$name_prefix}[list_type]' and @value='{$settings['list_type']}']";

    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');
    $this->assertAxes($axes);

    $edit = ["{$name_prefix}[list_type]" => array_rand($list_types)];

    $expected_items = [t('List type: %list_type', ['%list_type' => $edit["{$name_prefix}[list_type]"]])];
    if ($edit["{$name_prefix}[list_type]"] != 'dl') {
      $expected_items[] = t('Display as inline element');
    }
    $this->assertSummary($edit, $expected_items);

    // -- Unformatted list.
    $this->saveFormatterSettings('unformatted_list', $settings);

    $this->drupalPostAjaxForm($this->displayAdminPath, [], $this->fieldName . '_settings_edit');
    $this->assertAxes($general_axes);
    $expected_items = [t('Display as inline element')];
    $this->assertSummary([], $expected_items);
  }

  /**
   * Passes if expected field values were found on the page.
   */
  protected function assertFieldValues($axes, $delta = 0) {
    $settings = $this->getFormatterOptions()['settings'];

    foreach (['first', 'second'] as $index => $subfield) {

      $elements = $this->xpath($axes[$index]);
      if (count($elements) == 0) {
        $this->error(t('Xpath was not found: @xpath', ['@xpath' => $axes[$index]]));
      }
      else {
        $this->assertTrue(trim($elements[0]) == $this->values[$delta][$subfield], 'Valid value was found.');
        $this->assertTrue((string) $elements[0]->span[0] == $settings[$subfield]['prefix'], 'Prefix was found.');
        $this->assertTrue((string) $elements[0]->span[1] == $settings[$subfield]['suffix'], 'Suffix was found.');
      }

    }
  }

  /**
   * Passes if all summary items were found.
   */
  protected function assertSummary(array $edit = [], array $expected_items = []) {
    $edit = array_merge($edit, $this->generalSettingsEdit);
    $expected_items = array_merge($expected_items, $this->generalExpectedItems);

    $this->drupalPostAjaxForm(NULL, $edit, $this->fieldName . '_plugin_settings_update');
    $this->drupalPostForm(NULL, [], t('Save'));

    $summary = $this->xpath("//tr[@id='$this->fieldName']//div[@class='field-plugin-summary']")[0]->asXML();
    // Remove wrapper.
    $summary = str_replace(['<div class="field-plugin-summary">', '</div>'], '', $summary);
    $summary_items = explode('<br/>', $summary);

    $this->assertIdenticalArray($summary_items, $expected_items, 'Valid summary was found.');
  }

}
