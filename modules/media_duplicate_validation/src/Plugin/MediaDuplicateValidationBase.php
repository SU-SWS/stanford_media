<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaDuplicateValidationBase
 *
 * @package Drupal\media_duplicate_validation\Plugin
 */
abstract class MediaDuplicateValidationBase extends PluginBase implements MediaDuplicateValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function populateTable() {
    foreach (Media::loadMultiple() as $media) {
      $this->mediaSave($media);
    }
  }

}
