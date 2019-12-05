<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'yearonly_academic' formatter.
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
class MediaResponsiveImageFormatter extends MediaFormatterBase {

  /**
   * Get available responsive image styles.
   *
   * @return array
   *   Keyed array of image styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStyleOptions() {
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
  public function preRender($element) {
    $source_field = self::getSourceField($element['#media']);
    if ($element[$source_field]['#field_type'] != 'image') {
      return $element;
    }

    $element[$source_field]['#formatter'] = 'responsive_image';
    foreach (Element::children($element[$source_field]) as $delta) {
      $item = &$element[$source_field][$delta];
      $item['#theme'] = 'responsive_image_formatter';
      $item['#responsive_image_style_id'] = $element['#stanford_media_image_style'];

      if (isset($element['#stanford_media_url'])) {
        $item['#url'] = $element['#stanford_media_url'];
        $item['#attributes']['title'] = $element['#stanford_media_url_title'];
      }
    }

    return $element;
  }

}
