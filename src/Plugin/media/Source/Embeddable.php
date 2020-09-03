<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * @MediaSource(
 *   id = "embeddable",
 *   label = @Translation("Stanford Embedded Media"),
 *   description = @Translation("Embeds a third-party resource."),
 *   default_thumbnail_filename = "generic.png",
 *   providers = {"ArcGIS StoryMaps", "CircuitLab", "Codepen", "Dailymotion", "Facebook", "Flickr", "Getty Images", "Instagram", "Issuu", "Livestream", "MathEmbed", "Simplecast", "SlideShare", "SoundCloud", "Spotify", "Stanford Digital Repository", "Twitter"},
 *   allowed_field_types = {"string", "string_long"},
 * )
 */
class Embeddable extends OEmbed {

  /**
   * Gets the value for a metadata attribute for a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   * @param string $attribute_name
   *   Name of the attribute to fetch.
   *
   * @return mixed|null
   *   Metadata attribute value or NULL if unavailable.
   */
  public function getMetadata(MediaInterface $media, $name) {
    if ($this->hasUnstructured($media)) {
      return $this->getUnstructuredMetadata($media, $name);
    }
    return parent::getMetadata($media, $name);
  }

  /**
   * Gets the value for a metadata attribute for a given media item.
   * This is an alternate version to account for unstructured embeds.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   * @param string $attribute_name
   *   Name of the attribute to fetch.
   *
   * @return mixed|null
   *   Metadata attribute value or NULL if unavailable.
   */
  public function getUnstructuredMetadata(MediaInterface $media, $name) {
    switch ($name) {
      case 'default_name':
        if ($title = $this->getMetadata($media, 'title')) {
          return $title;
        }
        elseif ($url = $this->getMetadata($media, 'url')) {
          return $url;
        }
        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':
        parent::getMetadata($media, 'thumbnail_uri');
      case 'html':
        return $media->get('field_media_embeddable_code')->getValue();
      case 'type':
      case 'title':
      case 'author_name':
      case 'author_url':
      case 'provider_name':
      case 'provider_url':
      case 'cache_age':
      case 'thumbnail_width':
      case 'thumbnail_height':
      case 'url':
      case 'width':
      case 'height':
      default:
        return null;
    }
    return NULL;

  }


  /**
   * Is there a value for the oEmbed URL?
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return bool
   *   TRUE means it has an Unstructured embed, FALSE means that field is empty
   */
  public function hasOEmbed(MediaInterface $media) {
    return !empty($media->get('field_media_embeddable_oembed')->getValue());
  }

  /**
   * Is there a value for the Unstructured Embed?
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return bool
   *   TRUE means it has an Unstructured embed, FALSE means that field is empty
   */
  public function hasUnstructured(MediaInterface $media) {
    return !empty($media->get('field_media_embeddable_code')->getValue());
  }

  /**
   * {@inheritDoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'embeddable' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldValue(MediaInterface $media) {
    if ($this->hasUnstructured($media)) {
      $source_field = 'field_media_embeddable_code';
    }
    else {
      $source_field = $this->configuration['source_field'];
    }
    if (empty($source_field)) {
      throw new \RuntimeException('Source field for media source is not defined.');
    }
    $items = $media
      ->get($source_field);
    if ($items
      ->isEmpty()) {
      return NULL;
    }
    $field_item = $items
      ->first();
    return $field_item->{$field_item
      ->mainPropertyName()};
  }

}
