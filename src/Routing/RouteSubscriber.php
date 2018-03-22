<?php

namespace Drupal\stanford_media\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\stanford_media\Controller\MediaAdd;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.media.add_page')) {
      $route->setPath('/admin/content/media/add');
      $route->setDefault('_controller', MediaAdd::class . '::addPage');
    }
  }

}
