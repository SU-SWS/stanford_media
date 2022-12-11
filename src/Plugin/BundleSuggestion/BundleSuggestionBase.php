<?php

namespace Drupal\stanford_media\Plugin\BundleSuggestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\stanford_media\Plugin\BundleSuggestionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BundleSuggestionBase.
 *
 * @package Drupal\stanford_media\Plugin\BundleSuggestion
 */
abstract class BundleSuggestionBase extends PluginBase implements BundleSuggestionInterface, ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get all or some media bundles.
   *
   * @param array $bundles
   *   Optionally specify which media bundles to load.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   Keyed array of all media types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMediaBundles(array $bundles = []): array {
    return $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getName($input): ?string {
    return NULL;
  }

}
