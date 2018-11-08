<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image as OriginalImage;

/**
 * Image entity media source.
 */
class Image extends OriginalImage {

  /**
   * Key for "image caption" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_CAPTION = 'caption';

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = parent::getMetadataAttributes();

    $attributes += [
      static::METADATA_ATTRIBUTE_CAPTION => $this->t('Caption'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    if ($name != static::METADATA_ATTRIBUTE_CAPTION) {
      return parent::getMetadata($media, $name);
    }
    $media_type = MediaType::load($media->bundle());
    $field_map = $media_type->getFieldMap();
    if (empty($field_map['caption'])) {
      return;
    }
    return $media->get($field_map['caption'])->getString();
  }

}
