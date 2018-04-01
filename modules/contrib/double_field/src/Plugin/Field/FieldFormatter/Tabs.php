<?php

namespace Drupal\double_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementations for 'tabs' formatter.
 *
 * @FieldFormatter(
 *   id = "double_field_tabs",
 *   label = @Translation("Tabs"),
 *   field_types = {"double_field"}
 * )
 */
class Tabs extends Base {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element[0] = [
      '#theme' => 'double_field_tabs',
      '#items' => $items,
      '#settings' => $this->getSettings(),
      '#attached' => ['library' => ['double_field/tabs']],
    ];

    return $element;
  }

}
