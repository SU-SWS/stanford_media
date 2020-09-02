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
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'type' => $this->t('Resource type'),
      'title' => $this->t('Resource title'),
      'author_name' => $this->t('The name of the author/owner'),
      'author_url' => $this->t('The URL of the author/owner'),
      'provider_name' => $this->t("The name of the provider"),
      'provider_url' => $this->t('The URL of the provider'),
      'cache_age' => $this->t('Suggested cache lifetime'),
      'default_name' => $this->t('Default name of the media item'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
      'thumbnail_width' => $this->t('Thumbnail width'),
      'thumbnail_height' => $this->t('Thumbnail height'),
      'url' => $this->t('The source URL of the resource'),
      'width' => $this->t('The width of the resource'),
      'height' => $this->t('The height of the resource'),
      'html' => $this->t('The HTML representation of the resource'),
    ];
  }

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
   * {@inheritdoc}
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
        // $default_thumbnail_filename = $this->pluginDefinition['default_thumbnail_filename'];
        // return $this->configFactory->get('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
        parent::getMetadata($media, 'thumbnail_uri');
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
        return $media->get('field_media_embeddable_oembed')->getValue();

      case 'width':
      case 'height':
      case 'html':
        return $media->get('field_media_embeddable_code')->getValue();

      default:
        break;
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public function getOEmbedMetadata(MediaInterface $media, $name) {
    $media_url = $this->getSourceFieldValue($media);
    // The URL may be NULL if the source field is empty, in which case just
    // return NULL.
    if (empty($media_url)) {
      return NULL;
    }

    try {
      $resource_url = $this->urlResolver->getResourceUrl($media_url);
      $resource = $this->resourceFetcher->fetchResource($resource_url);
    }
    catch (ResourceException $e) {
      $this->messenger->addError($e->getMessage());
      return NULL;
    }

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
        return $this->getLocalThumbnailUri($resource) ?: parent::getMetadata($media, 'thumbnail_uri');

      case 'type':
        return $resource->getType();

      case 'title':
        return $resource->getTitle();

      case 'author_name':
        return $resource->getAuthorName();

      case 'author_url':
        return $resource->getAuthorUrl();

      case 'provider_name':
        $provider = $resource->getProvider();
        return $provider ? $provider->getName() : '';

      case 'provider_url':
        $provider = $resource->getProvider();
        return $provider ? $provider->getUrl() : NULL;

      case 'cache_age':
        return $resource->getCacheMaxAge();

      case 'thumbnail_width':
        return $resource->getThumbnailWidth();

      case 'thumbnail_height':
        return $resource->getThumbnailHeight();

      case 'url':
        $url = $resource->getUrl();
        return $url ? $url->toString() : NULL;

      case 'width':
        // Return $resource->getWidth();
        return '100%';

      case 'height':
        return $resource->getHeight();

      case 'html':
        return $resource->getHtml();

      default:
        break;
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
    if (!empty($media->get('field_media_embeddable_oembed')->getValue())) {
      return TRUE;
    }
    return FALSE;
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
    if (!empty($media->get('field_media_embeddable_code')->getValue())) {
      return TRUE;
    }
    return FALSE;
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
   * {@inheritDoc}
   */
  public function getSourceFieldDefinition(MediaTypeInterface $type) {
    // Nothing to do if no source field is configured yet.
    $field = $this->configuration['source_field'];
    if ($field) {
      // Even if we do know the name of the source field, there is no
      // guarantee that it already exists.
      $fields = $this->entityFieldManager
        ->getFieldDefinitions('media', $type
          ->id());
      return isset($fields[$field]) ? $fields[$field] : NULL;
    }
    return NULL;
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
