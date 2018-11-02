<?php

namespace Drupal\stanford_media\Plugin\media\Source;

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

}
