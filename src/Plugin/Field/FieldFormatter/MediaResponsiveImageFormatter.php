<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'media_responsive_image_formatter' formatter.
 *
 * @FieldFormatter (
 *   id = "media_responsive_image_formatter",
 *   label = @Translation("Media Responsive Image Style"),
 *   description = @Translation("Apply a responsive image style to image media
 *   items."), field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaResponsiveImageFormatter extends MediaImageFormatterBase {

  /**
   * {@inheritDoc}
   */
  protected function getStyleOptions(): array {
    $styles = $this->entityTypeManager->getStorage('responsive_image_style')
      ->loadMultiple();
    /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle $style */
    foreach ($styles as &$style) {
      $style = $style->label();
    }
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender($element): array {
    $source_field = self::getSourceField($element['#media']);
    // If the source field is not an image field, don't modify anything.
    if (empty($element[$source_field]['#field_type']) || $element[$source_field]['#field_type'] != 'image') {
      return $element;
    }

    $element[$source_field]['#formatter'] = 'responsive_image';
    foreach (Element::children($element[$source_field]) as $delta) {
      $item = &$element[$source_field][$delta];
      $item['#theme'] = 'responsive_image_formatter';
      $item['#responsive_image_style_id'] = $element['#stanford_media_image_style'];
      // If the field formatter is configured to remove the alt text.
      if (!empty($element['#stanford_media_remove_alt'])) {
        $item['#item']->set('alt', '');
      }

      if (isset($element['#stanford_media_url'])) {
        $item['#url'] = $element['#stanford_media_url'];
        $item['#attributes']['title'] = $element['#stanford_media_url_title'];
      }
    }

    return $element;
  }

}
