<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Interface BundleSuggestionInterface for Bundle Suggestion plugins.
 *
 * @package Drupal\stanford_media\Plugin
 */
interface BundleSuggestionInterface {

  /**
   * Find a matching media type bundle from the user entered data.
   *
   * @param string $input
   *   File uri, url, or other data that might be entered.
   *
   * @return \Drupal\media\MediaTypeInterface|null
   *   Media type object if matched.
   */
  public function getBundleFromString(string $input): ?MediaTypeInterface;

  /**
   * Get the name that should be used on the media entity.
   *
   * @param string $input
   *   User input data.
   *
   * @return string|null
   *   A name or null if none suggested.
   */
  public function getName(string $input): ?string;

}
