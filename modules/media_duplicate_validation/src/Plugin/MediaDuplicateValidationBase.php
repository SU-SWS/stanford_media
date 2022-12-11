<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for duplicate validation plugins.
 */
abstract class MediaDuplicateValidationBase extends PluginBase implements MediaDuplicateValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Cron queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Database logging service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('queue'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $database, QueueFactory $queue, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->queue = $queue;
    $this->logger = $logger_factory->get($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity): void {
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity): void {
  }

  /**
   * {@inheritdoc}
   */
  public function schema(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function populateTable(): void {
    $queue = $this->queue->get('media_duplicate_validation');

    $query = $this->database->select('media', 'm')
      ->fields('m', ['mid'])
      ->execute();
    while ($mid = $query->fetchField()) {
      $item = new \stdClass();
      $item->plugin = $this->getPluginId();
      $item->mid = $mid;
      $queue->createItem($item);
    }
  }

  /**
   * Get the file entity from the media entity source.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity.
   * @param array $applicable_types
   *   Optional array of allowed field types.
   *
   * @return \Drupal\file\FileInterface|null
   *   File entity or null if not applicable.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFile(MediaInterface $entity, array $applicable_types = []): ?FileInterface {
    $media_source = $entity->getSource();
    $definition = $media_source->getPluginDefinition();

    $applicable_types = $applicable_types ?: ['file', 'image', 'video'];

    // Only check for sources that attach to local files.
    if (array_intersect($definition['allowed_field_types'], $applicable_types)) {
      $fid = $entity->getSource()->getSourceFieldValue($entity);
      return $this->entityTypeManager->getStorage('file')->load($fid);
    }
    return NULL;
  }

}
