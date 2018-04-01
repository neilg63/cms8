<?php

namespace Drupal\double_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\double_field\Plugin\Field\FieldType\DoubleField as DoubleFieldItem;

/**
 * Base class for Double field formatters.
 */
abstract class Base extends FormatterBase {

  /**
   * Subfield types that can be rendered as a link.
   *
   * @var array
   */
  protected static $linkTypes = ['email', 'telephone', 'uri'];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    foreach (['first', 'second'] as $subfield) {
      $settings[$subfield] = [
        // Hidden option are useful to display data with Views module.
        'hidden' => FALSE,
        'prefix' => '',
        'suffix' => '',
        'link' => FALSE,
        'format_type' => 'medium',
      ];
    }
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    $types = DoubleFieldItem::subfieldTypes();
    $element = [];

    // General settings.
    foreach (['first', 'second'] as $subfield) {
      $type = $field_settings['storage'][$subfield]['type'];

      $title = $subfield == 'first' ? $this->t('First subfield') : $this->t('Second subfield');
      $title .= ' - ' . $types[$type];

      $element[$subfield] = [
        '#title' => $title,
        '#type' => 'details',
      ];

      $element[$subfield]['link'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display as link'),
        '#default_value' => $settings[$subfield]['link'],
        '#weight' => -10,
        '#access' => in_array($type, static::$linkTypes),
      ];

      if ($type == 'datetime_iso8601') {
        $format_types = DateFormat::loadMultiple();
        $date_formatter = \Drupal::service('date.formatter');
        $time = new DrupalDateTime();
        $options = [];
        foreach ($format_types as $type => $type_info) {
          $format = $date_formatter->format($time->getTimestamp(), $type);
          $options[$type] = $type_info->label() . ' (' . $format . ')';
        }
        $element[$subfield]['format_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Date format'),
          '#description' => $this->t('Choose a format for displaying the date.'),
          '#options' => $options,
          '#default_value' => $settings[$subfield]['format_type'],
        ];
      }
      else {
        $element[$subfield]['format_type'] = [
          '#type' => 'value',
          '#default_value' => $settings[$subfield]['format_type'],
        ];
      }

      $element[$subfield]['hidden'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hidden'),
        '#default_value' => $settings[$subfield]['hidden'],
      ];
      $element[$subfield]['prefix'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Prefix'),
        '#size' => 30,
        '#default_value' => $settings[$subfield]['prefix'],
      ];
      $element[$subfield]['suffix'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Suffix'),
        '#size' => 30,
        '#default_value' => $settings[$subfield]['suffix'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();

    $subfield_types = DoubleFieldItem::subfieldTypes();

    $summary = [];
    foreach (['first', 'second'] as $subfield) {
      $subfield_type = $field_settings['storage'][$subfield]['type'];
      $summary[] = new FormattableMarkup(
        '<b>@subfield - @subfield_type</b>',
        [
          '@subfield' => ($subfield == 'first' ? $this->t('First subfield') : $this->t('Second subfield')),
          '@subfield_type' => strtolower($subfield_types[$subfield_type]),
        ]
      );
      if ($subfield_type == 'datetime_iso8601') {
        $summary[] = $this->t('Date format: %format', ['%format' => $settings[$subfield]['format_type']]);
      }
      if (in_array($subfield_type, static::$linkTypes)) {
        $summary[] = $this->t('Link: %value', ['%value' => $settings[$subfield]['link'] ? $this->t('yes') : $this->t('no')]);
      }
      $summary[] = $this->t('Hidden: %value', ['%value' => $settings[$subfield]['hidden'] ? $this->t('yes') : $this->t('no')]);
      $summary[] = $this->t('Prefix: %prefix', ['%prefix' => $settings[$subfield]['prefix']]);
      $summary[] = $this->t('Suffix: %suffix', ['%suffix' => $settings[$subfield]['suffix']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];
    if (count($items) > 0) {
      // A field may appear multiple times in a single view. Since items are
      // passed by reference we need to ensure they are processed only once.
      $items = clone $items;
      $this->prepareItems($items);
      $elements = parent::view($items, $langcode);
    }
    return $elements;
  }

  /**
   * Prepare field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   List of field items.
   */
  protected function prepareItems(FieldItemListInterface $items) {

    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      foreach (['first', 'second'] as $subfield) {

        if ($settings[$subfield]['hidden']) {
          $item->{$subfield} = FALSE;
        }
        else {

          $type = $field_settings['storage'][$subfield]['type'];

          if ($type == 'boolean') {
            $item->{$subfield} = $field_settings[$subfield][$item->{$subfield} ? 'on_label' : 'off_label'];
          }

          if ($type == 'datetime_iso8601' && $item->{$subfield} && !$field_settings[$subfield]['list']) {
            $storage_format = $field_settings['storage'][$subfield]['datetime_type'] == 'datetime'
              ? DoubleFieldItem::DATETIME_DATETIME_STORAGE_FORMAT
              : DoubleFieldItem::DATETIME_DATE_STORAGE_FORMAT;

            $date = DrupalDateTime::createFromFormat($storage_format, $item->{$subfield});
            $date_formatter = \Drupal::service('date.formatter');
            $timestamp = $date->getTimestamp();
            $formatted_date = $date_formatter->format($timestamp, $settings[$subfield]['format_type']);
            $iso_date = $date_formatter->format($timestamp, 'custom', 'Y-m-d\TH:i:s') . 'Z';
            $item->{$subfield} = [
              '#theme' => 'time',
              '#text' => $formatted_date,
              '#html' => FALSE,
              '#attributes' => [
                'datetime' => $iso_date,
              ],
              '#cache' => [
                'contexts' => [
                  'timezone',
                ],
              ],
            ];
          }

          // Replace the value with its label if possible.
          $original_value[$subfield] = $item->{$subfield};
          if ($field_settings[$subfield]['list']) {
            if (isset($field_settings[$subfield]['allowed_values'][$item->{$subfield}])) {
              $item->{$subfield} = $field_settings[$subfield]['allowed_values'][$item->{$subfield}];
            }
            else {
              $item->{$subfield} = FALSE;
            }
          }

          if (!empty($settings[$subfield]['link'])) {
            $value = isset($original_value) ? $original_value[$subfield] : $item->{$subfield};
            switch ($type) {
              case 'email':
                $item->{$subfield} = [
                  '#type' => 'link',
                  '#title' => $item->{$subfield},
                  '#url' => Url::fromUri('mailto:' . $value),
                ];
                break;

              case 'telephone':
                $item->{$subfield} = [
                  '#type' => 'link',
                  '#title' => $item->{$subfield},
                  '#url' => Url::fromUri('tel:' . rawurlencode(preg_replace('/\s+/', '', $value))),
                  '#options' => ['external' => TRUE],
                ];
                break;

              case 'uri':
                $item->{$subfield} = [
                  '#type' => 'link',
                  '#title' => $item->{$subfield},
                  '#url' => Url::fromUri($value),
                  '#options' => ['external' => TRUE],
                ];
                break;

            }
          }

        }

      }
      $items[$delta] = $item;
    }
  }

}
