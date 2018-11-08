<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
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
class MediaImageFormatter extends MediaFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getStyleOptions() {
    return image_style_options(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($element) {
    $element['field_media_image']['#formatter'] = 'image';
    foreach (Element::children($element['field_media_image']) as $delta) {
      $item = &$element['field_media_image'][$delta];
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
