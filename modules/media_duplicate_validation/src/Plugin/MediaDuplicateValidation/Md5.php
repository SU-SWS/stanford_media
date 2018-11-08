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

  /**
   * {@inheritdoc}
   */
  public function isUnique($uri) {
    $md5 = md5(file_get_contents($uri));
    $existing_md5s = $this->getExistingMd5();

    return !in_array($md5, $existing_md5s);
  }

  /**
   * Get existing md5 data.
   *
   * @return array
   *   Keyed array of existing media md5 data.
   */
  protected function getExistingMd5() {
    if ($cache = $this->cache->get($this->getCacheId())) {
      return $cache->data;
    }

    $md5s = [];

    // This should only need to be executed 1 time ever. All other md5s get
    // set during entity save.
    /** @var Media $media */
    foreach (Media::loadMultiple() as $media) {
      $file = File::load($media->getSource()->getSourceFieldValue($media));
      if ($file) {
        $md5s[$media->id()] = md5(file_get_contents($file->getFileUri()));
      }
    }
    $this->cache->set($this->getCacheId(), $md5s);
    return $md5s;
  }

  /**
   * {@inheritdoc}
   */
  public function getSimilarItems($uri) {
    $md5 = md5(file_get_contents($uri));
    $existing_md5s = $this->getExistingMd5();
    if ($media_id = array_search($md5, $existing_md5s)) {
      return [Media::load($media_id)];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity) {
    $md5s = [];
    if ($cache = $this->cache->get($this->getCacheId())) {
      $md5s = $cache->data;
    }

    $file = File::load($entity->getSource()->getSourceFieldValue($entity));
    if ($file) {
      $md5s[$entity->id()] = md5(file_get_contents($file->getFileUri()));
    }

    $this->cache->set($this->getCacheId(), $md5s);
  }

}
