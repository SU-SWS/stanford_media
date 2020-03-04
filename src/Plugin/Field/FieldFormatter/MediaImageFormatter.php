<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'media_image_formatter' formatter.
 *
 * @FieldFormatter (
 *   id = "media_image_formatter",
 *   label = @Translation("Media Image Style"),
 *   description = @Translation("Apply an image style to image media items."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaImageFormatter extends MediaImageFormatterBase {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  protected function getStyleOptions() {
    return image_style_options(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($element) {
    $source_field = self::getSourceField($element['#media']);

    if (empty($element[$source_field]['#field_type']) || $element[$source_field]['#field_type'] != 'image') {
      return $element;
    }

    $element[$source_field]['#formatter'] = 'image';
    foreach (Element::children($element[$source_field]) as $delta) {
      $item = &$element[$source_field][$delta];
      $item['#theme'] = 'image_formatter';
      $item['#image_style'] = $element['#stanford_media_image_style'];

      if (isset($element['#stanford_media_url'])) {
        $item['#url'] = $element['#stanford_media_url'];
        $item['#attributes']['title'] = $element['#stanford_media_url_title'];
      }

    }
    return $element;
  }

}
