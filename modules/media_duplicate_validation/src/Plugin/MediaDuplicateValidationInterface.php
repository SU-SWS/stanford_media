<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\media\MediaInterface;

/**
 * Interface MediaDuplicateValidationInterface.
 *
 * @package Drupal\media_duplicate_validation\Plugin
 */
interface MediaDuplicateValidationInterface {

  /**
   * Ask if the provided file uri is unique in some sense.
   *
   * @param string $uri
   *   File path uri.
   *
   * @return bool
   *   If the file is unique as defined by the plugin.
   */
  public function isUnique($uri);

  /**
   * Find any similar media entities to the given file uri.
   *
   * @param string $uri
   *   File path uri.
   *
   * @return \Drupal\media\Entity\Media[]
   *   Array of similar entities.
   */
  public function getSimilarItems($uri);

  /**
   * Perform any necessary actions after a media entity has been saved.
   *
   * Such actions might be to cache the contents or the file name for quicker
   * lookup at a later date.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity that has been saved.
   */
  public function mediaSave(MediaInterface $entity);

  /**
   * @param \Drupal\media\MediaInterface $entity
   *
   * @return mixed
   */
  public function mediaDelete(MediaInterface $entity);

}
