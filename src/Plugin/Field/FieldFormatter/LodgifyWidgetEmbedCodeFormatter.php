<?php

declare(strict_types = 1);

namespace Drupal\lodgify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field formatter plugin to allow rendering of Lodgify embed code.
 *
 * @FieldFormatter(
 *   id = "lodgify_widget_embed_code",
 *   label = @Translation("Lodgify widget embed code"),
 *   field_types = {"string_long"},
 * )
 */
final class LodgifyWidgetEmbedCodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
        '#allowed_tags' => ['div', 'script', 'style'],
      ];
    }
    return $element;
  }

}
