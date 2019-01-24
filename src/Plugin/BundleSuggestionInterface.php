<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface BundleSuggestionInterface for Bundle Suggestion plugins.
 *
 * @package Drupal\stanford_media\Plugin
 */
interface BundleSuggestionInterface extends ContainerFactoryPluginInterface {

  /**
   * Find a matching media type bundle from the user entered data.
   *
   * @param string $input
   *   File uri, url, or other data that might be entered.
   *
   * @return \Drupal\media\Entity\MediaType|null
   *   Media type object if matched.
   */
  public function getBundleFromString($input);

  /**
   * Get the name that should be used on the media entity.
   *
   * @param string $input
   *   User input data.
   *
   * @return string|null
   *   A name or null if none suggested.
   */
  public function getName($input);

}
