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

  /**
   * Go through all available plugins and validate one of them allows the code.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return bool
   *   True if one of the plugins validates successfully.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function embedCodeIsAllowed($code): bool {
    foreach ($this->getDefinitions() as $definition) {
      /** @var \Drupal\stanford_media\Plugin\EmbedValidatorInterface $plugin */
      $plugin = $this->createInstance($definition['id']);
      if ($plugin->isEmbedCodeAllowed($code)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Modify the raw html embed code using the applicable validation plugin.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return string
   *   Modified html embed code.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function prepareEmbedCode($code): string {
    foreach ($this->getDefinitions() as $definition) {
      /** @var \Drupal\stanford_media\Plugin\EmbedValidatorInterface $plugin */
      $plugin = $this->createInstance($definition['id']);
      if ($plugin->isEmbedCodeAllowed($code)) {
        return $plugin->prepareEmbedCode($code);
      }
    }
    return $code;
  }

}
