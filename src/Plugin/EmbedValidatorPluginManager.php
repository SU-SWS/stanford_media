<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Oembed validator plugin plugin manager.
 */
class EmbedValidatorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new EmbedValidatorPluginManager object.
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
    parent::__construct('Plugin/EmbedValidator', $namespaces, $module_handler, 'Drupal\stanford_media\Plugin\EmbedValidatorInterface', 'Drupal\stanford_media\Annotation\EmbedValidator');

    $this->alterInfo('stanford_media_embed_validator_plugin_info');
    $this->setCacheBackend($cache_backend, 'stanford_media_embed_validator_plugin_plugins');
  }

}
