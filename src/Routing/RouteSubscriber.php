<?php

namespace Drupal\stanford_media\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\stanford_media\Controller\MediaAdd;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Route subscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.media.add_page')) {
      $route->setDefault('_controller', MediaAdd::class . '::addPage');
    }
    if (
      $this->configFactory->get('media.settings')->get('standalone_url') &&
      ($route = $collection->get('entity.media.canonical'))
    ) {
      $route->setRequirement('_entity_access', 'media.view_standalone');
    }
  }

}
