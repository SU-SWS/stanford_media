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
   * Find any similar media entities to the given file uri.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media item entity.
   *
   * @return \Drupal\media\Entity\Media[]
   *   Array of similar entities.
   */
  public function getSimilarItems(MediaInterface $entity);

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
   * Perform any necessary action when a media entity is deleted.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Deleted entity.
   */
  public function mediaDelete(MediaInterface $entity);

  /**
   * If the plugin requires a database table, define it here.
   *
   * @see hook_schema()
   *
   * @return array
   *   Database schema definition.
   */
  public function schema();

  /**
   * Perform necessary action when the table is created.
   */
  public function populateTable();

}
