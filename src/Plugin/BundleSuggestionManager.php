<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\stanford_media\Annotation\BundleSuggestion;

/**
 * Class MediaEmbedManager.
 *
 * @package Drupal\stanford_media
 */
class BundleSuggestionManager extends DefaultPluginManager {

  /**
   * Constructs a MediaEmbedManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/BundleSuggestion',
      $namespaces,
      $module_handler,
      BundleSuggestionInterface::class,
      BundleSuggestion::class
    );
    $this->alterInfo('bundle_suggestion_info');
    $this->setCacheBackend($cache_backend, 'bundle_suggestion_info_plugins');
  }

  /**
   * With a provided input string from the user, find an media bundle to match.
   *
   * @param string $input
   *
   * @return \Drupal\media\Entity\MediaType|null
   */
  public function getSuggestedBundle($input) {
    foreach ($this->getDefinitions() as $definition) {
      try {
        /** @var \Drupal\stanford_media\Plugin\BundleSuggestionInterface $plugin */
        $plugin = $this->createInstance($definition['id']);
      }
      catch (\Exception $e) {
        // The plugin has some dependency that isn't resolved. So we can skip
        // it.
        continue;
      }

      if ($bundle = $plugin->getBundleFromString($input)) {
        return $bundle;
      }
    }

  }

}
