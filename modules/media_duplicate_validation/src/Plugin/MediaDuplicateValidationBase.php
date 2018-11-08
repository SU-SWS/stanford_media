<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaDuplicateValidationBase
 *
 * @package Drupal\media_duplicate_validation\Plugin
 */
abstract class MediaDuplicateValidationBase extends PluginBase implements MediaDuplicateValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache_backend) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity) {
    if ($cache = $this->cache->get($this->getCacheId())) {
      unset($cache->data[$entity->id()]);
      $this->cache->set($this->getCacheId(), $cache->data);
    }
  }

  /**
   * Get the cache id for the current plugin.
   *
   * @return string
   *   Cache Cid.
   */
  protected function getCacheId() {
    return 'media_duplicate_validation:' . $this->pluginId;
  }

}
