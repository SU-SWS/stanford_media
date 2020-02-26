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
 *   default_thumbnail_filename = "google-form.png"
 * )
 */
class GoogleForm extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  public function getMetadataAttributes() {
    return [
      'id' => $this->t('Form ID'),
    ];
  }

  public function getSourceFieldConstraints() {
    return [
      'google_forms' => [],
    ];
  }

}
