<?php

namespace Drupal\stanford_media\Plugin\EntityUsage\Track;

use Drupal\Component\Utility\Html;
use Drupal\entity_usage\Plugin\EntityUsage\Track\TextFieldEmbedBase;

/**
 * Tracks usage of drupal-media tags in wysiwyg fields.
 *
 * Remove if https://www.drupal.org/project/entity_usage/issues/3081407 is
 * resolved.
 *
 * @EntityUsageTrack(
 *   id = "media_embed",
 *   label = @Translation("Media Embed"),
 *   description = @Translation("Tracks relationships created with 'Media Embed' in formatted text fields."),
 *   field_types = {"text", "text_long", "text_with_summary"},
 * )
 */
class MediaEmbed extends TextFieldEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];
    foreach ($xpath->query('//drupal-media[@data-entity-type and @data-entity-uuid]') as $node) {
      $entities[$node->getAttribute('data-entity-uuid')] = $node->getAttribute('data-entity-type');
    }
    return $entities;
  }

}
