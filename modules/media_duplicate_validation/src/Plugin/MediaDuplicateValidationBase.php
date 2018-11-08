<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaDuplicateValidationBase
 *
 * @package Drupal\media_duplicate_validation\Plugin
 */
abstract class MediaDuplicateValidationBase implements MediaDuplicateValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.default')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache_backend) {
    $this->cache = $cache_backend;
  }

}
