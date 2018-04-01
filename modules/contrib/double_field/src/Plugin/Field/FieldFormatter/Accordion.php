<?php

namespace Drupal\double_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementations for 'accordion' formatter.
 *
 * @FieldFormatter(
 *   id = "double_field_accordion",
 *   label = @Translation("Accordion"),
 *   field_types = {"double_field"}
 * )
 */
class Accordion extends Base {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element[0] = [
      '#theme' => 'double_field_accordion',
      '#items' => $items,
      '#settings' => $this->getSettings(),
      '#attached' => ['library' => ['double_field/accordion']],
    ];

    return $element;
  }

}
