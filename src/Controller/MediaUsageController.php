<?php

namespace Drupal\stanford_media\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaUsageController extends ControllerBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_usage.usage')
    );
  }

  /**
   * Controller constructor.
   *
   * @param \Drupal\entity_usage\EntityUsageInterface $entityUsage
   *   Entity usage service.
   */
  public function __construct(protected EntityUsageInterface $entityUsage) {}

  /**
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function title(MediaInterface $media) {
    return $this->t('Media Usage: @title', ['@title' => $media->label()]);
  }

  /**
   * Controller table display to show the list of entities using a media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return array
   *   Render array.
   */
  public function view(MediaInterface $media) {
    // Create a table to display a link to each of the sources from the entity usage.
    $build = [
      '#theme' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Operations'),
      ],
      '#rows' => [],
    ];
    // Cache exists, use that.
    $cache = $this->cache()->get('stanford_media:media_usage:' . $media->id());
    if ($cache) {
      $build['#rows'] = $cache->data;
      return $build;
    }

    $cache_tags = ['media_usage:media:' . $media->id()];

    foreach ($this->getParentEntityUses($media) as $parent_entity) {
      $row = [];
      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $parent_entity->label(),
        '#url' => $parent_entity->toUrl(),
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $this->getOperationLinks($parent_entity),
      ];
      $build['#rows'][] = $row;
    }
    $this->cache()
      ->set('stanford_media:media_usage:' . $media->id(), $build['#rows'], CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    return $build;
  }

  protected function getOperationLinks(EntityInterface $entity) {
    $links = [];
    if ($entity->hasLinkTemplate('canonical') && $entity->access('view')) {
      $links['view'] = [
        'title' => $this->t('View'),
        'url' => $entity->toUrl(),
      ];
    }

    if ($entity->hasLinkTemplate('edit-form') && $entity->access('update')) {
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    return $links;
  }

  /**
   * Get the parent entity of a media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of parent entities.
   */
  protected function getParentEntityUses(MediaInterface $media) {
    $sources = $this->entityUsage->listSources($media);
    $deduped = [];
    $parent_entities = [];

    /// Loop through the sources and create a link to the entity.
    foreach ($sources as $entity_type => $entity_ids) {
      $entities = $this->entityTypeManager()
        ->getStorage($entity_type)
        ->loadMultiple(array_keys($entity_ids));

      foreach ($entities as $entity) {
        $parent_entity = $this->getParentEntity($entity);

        // Check if we've loaded this entity already.
        if (!$parent_entity || isset($deduped[$parent_entity->id()])) {
          continue;
        }

        $deduped[$parent_entity->id()] = TRUE;
        $parent_entities[] = $parent_entity;
      }
    }
    return $parent_entities;
  }

  /**
   * Get the parent entity of an entity like a paragraph.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Parent entity, or the original entity if it has no parent.
   */
  protected function getParentEntity(EntityInterface $entity) {
    if (method_exists($entity, 'getParentEntity') && $entity->getParentEntity()) {
      return $this->getParentEntity($entity->getParentEntity());
    }
    return $entity->hasLinkTemplate('canonical') ? $entity : NULL;
  }

}
