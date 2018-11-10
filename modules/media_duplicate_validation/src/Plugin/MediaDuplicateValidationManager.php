<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media_duplicate_validation\Annotation\MediaDuplicateValidation;

/**
 * Class MediaDuplicateValidationManager.
 *
 * @package Drupal\media_duplicate_validation\Plugin
 */
class MediaDuplicateValidationManager extends DefaultPluginManager {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a MediaDuplicateValidationManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, Connection $database) {
    parent::__construct(
      'Plugin/MediaDuplicateValidation',
      $namespaces,
      $module_handler,
      MediaDuplicateValidationInterface::class,
      MediaDuplicateValidation::class
    );
    $this->alterInfo('media_duplicate_validation_info');
    $this->setCacheBackend($cache_backend, 'media_duplicate_validation_info_plugins');
    $this->database = $database;
  }

  /**
   * Build plugin tables if needed.
   */
  public function buildPluginSchemas() {
    foreach ($this->getDefinitions() as $plugin_definition) {
      /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
      $plugin = $this->createInstance($plugin_definition['id']);

      foreach ($plugin->schema() as $name => $table_definition) {
        if (!$this->database->schema()->tableExists($name)) {
          $this->database->schema()->createTable($name, $table_definition);
          $plugin->populateTable();
        }
      }
    }
  }

}
