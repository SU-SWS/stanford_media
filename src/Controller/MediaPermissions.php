<?php

declare(strict_types=1);

namespace Drupal\stanford_media\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media permissions callback.
 */
class MediaPermissions extends ControllerBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Permissions callback constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Permissions callback to build standalone access permissions for each type.
   *
   * @return array
   *   Permissions for each media type.
   */
  public function standAlonePermissions() {
    if (!$this->configFactory->get('media.settings')->get('standalone_url')) {
      return [];
    }
    $media_types = $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple();
    $permissions = [];
    foreach ($media_types as $type) {
      $permission = sprintf('view %s media standalone url', $type->id());
      $permissions[$permission] = ['title' => $this->t('View %name standalone url', ['%name' => $type->label()])];
    }
    return $permissions;
  }

}
