<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
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
   * Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a BundleSuggestionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Field manager service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $field_manager) {
    parent::__construct(
      'Plugin/BundleSuggestion',
      $namespaces,
      $module_handler,
      BundleSuggestionInterface::class,
      BundleSuggestion::class
    );
    $this->alterInfo('bundle_suggestion_info');
    $this->setCacheBackend($cache_backend, 'bundle_suggestion_info_plugins');
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();

    $valid_definitions = [];
    // Find out which plugins should be used based on their field_types.
    foreach ($definitions as $plugin_id => $definition) {
      foreach ($definition['field_types'] as $field_type) {

        // A field for this plugin exists, so we can use this plugin.
        if ($this->fieldManager->getFieldMapByFieldType($field_type)) {
          $valid_definitions[$plugin_id] = $definition;
          continue 2;
        }
      }
    }

    return $valid_definitions;
  }

  /**
   * With a provided input string from the user, find an media bundle to match.
   *
   * @param string $input
   *   User entered data, such as a url or file path.
   *
   * @return \Drupal\media\Entity\MediaType|null
   *   Suggested bundle to match the input.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSuggestedBundle($input) {
    foreach ($this->getDefinitions() as $definition) {
      /** @var \Drupal\stanford_media\Plugin\BundleSuggestionInterface $plugin */
      $plugin = $this->createInstance($definition['id']);
      if ($bundle = $plugin->getBundleFromString($input)) {
        return $bundle;
      }
    }
  }

}
