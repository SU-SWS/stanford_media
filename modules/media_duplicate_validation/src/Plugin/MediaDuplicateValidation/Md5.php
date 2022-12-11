<?php

namespace Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation;

use Drupal\media\MediaInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationBase;

/**
 * Duplication plugin to check md5 hash of the entire file contents.
 *
 * @MediaDuplicateValidation(
 *   id = "md5"
 * )
 */
class Md5 extends MediaDuplicateValidationBase {

  /**
   * Database table used for this plugin.
   */
  const DATABASE_TABLE = self::DATABASE_PREFIX . 'md5';

  /**
   * {@inheritdoc}
   */
  public function getSimilarItems(MediaInterface $entity): array {
    $file = $this->getFile($entity);
    if (!$file) {
      return [];
    }

    $md5 = md5(@file_get_contents($file->getFileUri()));
    $query = $this->database->select(self::DATABASE_TABLE, 't')
      ->fields('t', ['mid'])
      ->condition('md5', $md5);
    // If the media entity hasn't been saved yet, it wont have an ID.
    if ($entity->id()) {
      $query->condition('mid', $entity->id(), '<>');
    }
    $query_result = $query->execute();

    $similar_media = [];
    $key = 100;
    $media_storage = $this->entityTypeManager->getStorage('media');
    while ($media_id = $query_result->fetchField()) {
      // If the md5 are the same, the file is 100% identical. There might be
      // multiple duplicates, so each key will decrease a tiny bit to allow it
      // to still be in the list of similar items.
      $similar_media["$key"] = $media_storage->load($media_id);
      $key -= '.01';
    }
    return array_filter($similar_media, function (MediaInterface $media = NULL) use ($entity) {
      return $media && $media->bundle() == $entity->bundle();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity): void {
    parent::mediaSave($entity);

    // When generating content with devel, we have to check if the entity has
    // a source.
    if (!$entity->getSource()) {
      return;
    }

    $file = $this->entityTypeManager->getStorage('file')
      ->load($entity->getSource()->getSourceFieldValue($entity));

    if ($file) {
      $this->database->merge(self::DATABASE_TABLE)
        ->fields([
          'mid' => $entity->id(),
          'md5' => md5(@file_get_contents($file->getFileUri())),
        ])
        ->key('mid', $entity->id())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity): void {
    parent::mediaDelete($entity);
    $this->database->delete(self::DATABASE_TABLE)
      ->condition('mid', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function schema(): array {
    $schema = parent::schema();
    $schema[self::DATABASE_TABLE] = [
      'description' => 'Media validation information for md5 plugin',
      'fields' => [
        'mid' => [
          'description' => 'The media entity ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'md5' => [
          'description' => 'The md5 hash of the media file.',
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '254',
        ],
      ],
      'primary key' => ['mid'],
    ];
    return $schema;
  }

}
