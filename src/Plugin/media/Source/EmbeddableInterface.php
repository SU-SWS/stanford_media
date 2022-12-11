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
  public function hasUnstructured(MediaInterface $media): bool;

  /**
   * Go through all available plugins and validate one of them allows the code.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return bool
   *   True if one of the plugins validates successfully.
   */
  public function embedCodeIsAllowed($code): bool;

  /**
   * Modify the raw html embed code using the applicable validation plugin.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return string
   *   Modified html embed code.
   */
  public function prepareEmbedCode($code): string;

}
