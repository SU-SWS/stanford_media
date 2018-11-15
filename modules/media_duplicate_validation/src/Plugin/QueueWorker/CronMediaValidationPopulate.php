<?php

namespace Drupal\media_duplicate_validation\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media\Entity\Media;
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
   * Media Duplicate validation manger service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('plugin.manager.media_duplicate_validation'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaDuplicateValidationManager $duplication_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->duplicationManager = $duplication_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
    $plugin = $this->duplicationManager->createInstance($data->plugin);
    if ($media = Media::load($data->mid)) {
      // Perform the media save method which should store any necessary data.
      $plugin->mediaSave($media);
    }
  }

}
