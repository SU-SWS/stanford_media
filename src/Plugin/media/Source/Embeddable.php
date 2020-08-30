<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\OEmbed;
use \Drupal\media\MediaInterface;

/**
 * @MediaSource(
 *   id = "embeddable",
 *   label = @Translation("Stanford Embedded Media"),
 *   description = @Translation("Embeds a third-party resource."),
 *   providers = {"ArcGIS StoryMaps", "CircuitLab", "Codepen", "Dailymotion", "Facebook", "Flickr", "Getty Images", "Instagram", "Issuu", "Livestream", "MathEmbed", "Simplecast", "SlideShare", "SoundCloud", "Spotify", "Stanford Digital Repository", "Twitter"},
 *   allowed_field_types = {"string"},
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
  public function getMetadata(MediaInterface $media, $name){
    if ($this->isUnstructured($media)){
      return $this->getUnstructuredMetadata($media, $name);
    }
    return $this->getOEmbedMetadata($media, $name);
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
      case 'html':
          return null;

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
        //return $resource->getWidth();
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
   * Determine if this is an oEmbed, or an Unstructured Embed
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return bool
   *   TRUE means it is an Unstructured embed, FALSE means it is an oEmbed
   */
  public function isUnstructured(MediaInterface $media) {
    if (!empty($media->get('field_media_embeddable_code')->getValue())){
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


}
