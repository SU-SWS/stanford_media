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
   * {@inheritDoc}
   */
  public function getMetadataAttributes() {
    return [
      self::METADATA_ATTRIBUTE_ID => $this->t('Form ID'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case self::METADATA_ATTRIBUTE_ID:
        return $this->getId($media);

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
    $url = $media->get($this->configuration['source_field'])->getValue()['value'];
    preg_match('/google.*forms\/([^ ]*)\/viewform/', $url, $form_id);
    return $form_id['0'];
  }

}
