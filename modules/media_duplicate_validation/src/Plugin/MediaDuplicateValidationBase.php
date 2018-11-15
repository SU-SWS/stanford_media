<?php

namespace Drupal\media_duplicate_validation\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file\Entity\File;
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

  /**
   * Get the file entity from the media entity source.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity.
   * @param array $applicable_types
   *   Optional array of allowed field types.
   *
   * @return \Drupal\file\Entity\File|null
   *   File entity or null if not applicable.
   */
  protected function getFile(MediaInterface $entity, array $applicable_types = []) {
    $media_source = $entity->getSource();
    $definition = $media_source->getPluginDefinition();

    $applicable_types = $applicable_types ?: ['file', 'image', 'video'];

    // Only check for sources that attach to local files.
    if (array_intersect($definition['allowed_field_types'], $applicable_types)) {
      $fid = $entity->getSource()->getSourceFieldValue($entity);
      return File::load($fid);
    }
  }

}
