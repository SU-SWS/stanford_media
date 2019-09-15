<?php

namespace Drupal\media_duplicate_validation\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A cron worker to populate validation plugin data.
 *
 * @QueueWorker(
 *   id = "media_duplicate_validation",
 *   title = @Translation("Media Duplicate Validation"),
 *   cron = {"time" = 60}
 * )
 */
class CronMediaValidationPopulate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Media Duplicate validation manger service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.media_duplicate_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MediaDuplicateValidationManager $duplication_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->duplicationManager = $duplication_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
    $plugin = $this->duplicationManager->createInstance($data->plugin);
    $media = $this->entityTypeManager->getStorage('media')->load($data->mid);

    if ($media) {
      // Perform the media save method which should store any necessary data.
      $plugin->mediaSave($media);
    }
  }

}
