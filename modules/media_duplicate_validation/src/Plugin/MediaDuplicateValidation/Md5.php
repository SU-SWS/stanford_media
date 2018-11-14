<?php

namespace Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationBase;

/**
 * Class Md5
 *
 * @MediaDuplicateValidation(
 *   id = "md5"
 * )
 */
class Md5 extends MediaDuplicateValidationBase {

  const DATABASE_TABLE = 'media_validate_md5';

  /**
   * {@inheritdoc}
   */
  public function isUnique($uri) {
    return empty($this->getSimilarItems($uri));
  }

  /**
   * {@inheritdoc}
   */
  public function getSimilarItems($uri) {
    $md5 = md5(file_get_contents($uri));
    $query = $this->database->select(self::DATABASE_TABLE, 't')
      ->fields('t', ['mid'])
      ->condition('md5', $md5)
      ->execute();

    $similar = [];
    while ($media_id = $query->fetchField()) {
      /** @var MediaInterface $media */
      $media = Media::load($media_id);
      $file = File::load($media->getSource()->getSourceFieldValue($media));
      if ($file && $file->getFileUri() != $uri) {
        $similar[$media_id] = $media;
      }
    }

    return $similar;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity) {
    $file = File::load($entity->getSource()->getSourceFieldValue($entity));
    if ($file) {
      $this->database->merge(self::DATABASE_TABLE)
        ->fields([
          'mid' => $entity->id(),
          'md5' => md5(file_get_contents($file->getFileUri())),
        ])
        ->key('mid', $entity->id())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity) {
    $this->database->delete(self::DATABASE_TABLE)
      ->condition('mid', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
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
