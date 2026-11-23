<?php

namespace Drupal\stanford_media;

use Drupal\media\MediaInterface;

/**
 * Defines the Stanford Media helper service.
 */
interface StanfordMediaInterface {

  /**
   * Delete any files associated with this media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   */
  public function deleteMediaFiles(MediaInterface $media): void;

}
