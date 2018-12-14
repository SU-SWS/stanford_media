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

}
