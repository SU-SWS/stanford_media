<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;

/**
 * Interface EmbeddableInterface.
 *
 * @package Drupal\stanford_media\Plugin\media\Source
 */
interface EmbeddableInterface extends OEmbedInterface {

  /**
   * Is there a value for the Unstructured Embed?
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return bool
   *   TRUE means it has an Unstructured embed, FALSE means that field is empty
   */
  public function hasUnstructured(MediaInterface $media);

}
