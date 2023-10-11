<?php

declare(strict_types = 1);

namespace Drupal\lodgify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field formatter plugin for rendering of Lodgify description HTML.
 *
 * @FieldFormatter(
 *   id = "lodgify_description",
 *   label = @Translation("Lodgify description"),
 *   field_types = {"string_long"},
 * )
 */
final class LodgifyDescriptionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
        '#allowed_tags' => ['p', 'br'],
      ];
    }
    return $element;
  }

}
