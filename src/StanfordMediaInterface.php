<?php

namespace Drupal\stanford_media;

use Drupal\media\MediaInterface;

/**
 * Interface StanfordMediaInterface.
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
