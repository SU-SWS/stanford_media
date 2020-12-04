<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Provides media source plugin for Google Forms iframe.
 *
 * @MediaSource(
 *   id = "google_form",
 *   label = @Translation("Google Form"),
 *   description = @Translation("Embed a google form."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "generic.png"
 * )
 */
class GoogleForm extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * Key for "Form ID" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_ID = 'id';

  /**
   * Field for the height attribute on the iframe.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_HEIGHT = 'height';

  /**
   * {@inheritDoc}
   */
  public function getMetadataAttributes() {
    return [
      self::METADATA_ATTRIBUTE_ID => $this->t('Form ID'),
      self::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case self::METADATA_ATTRIBUTE_ID:
        return $this->getId($media);

      case self::METADATA_ATTRIBUTE_HEIGHT:
        return $this->getIframeHeight($media);
    }
    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritDoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'google_forms' => [],
    ];
  }

  /**
   * Get the form ID from the media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return string
   *   Google form id.
   */
  protected function getId(MediaInterface $media) {
    preg_match('/google.*forms\/([^ ]*)\/viewform/', $this->getSourceFieldValue($media), $form_id);
    return $form_id[1];
  }

  /**
   * Get the height of the iframe from the field value if there is one.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return int
   *   Iframe height in pixels.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getIframeHeight(MediaInterface $media): int {
    $field_map = $this->entityTypeManager->getStorage('media_type')
      ->load($media->bundle())
      ->getFieldMap();
    if (isset($field_map[self::METADATA_ATTRIBUTE_HEIGHT]) && $media->hasField($field_map[self::METADATA_ATTRIBUTE_HEIGHT])) {
      return (int) $media->get($field_map[self::METADATA_ATTRIBUTE_HEIGHT])
        ->getString() ?: 600;
    }
    return 600;
  }

}
