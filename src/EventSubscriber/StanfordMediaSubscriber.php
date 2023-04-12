<?php

namespace Drupal\stanford_media\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Drupal\entity_usage\Events\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stanford Media event subscriber.
 */
class StanfordMediaSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Events::USAGE_REGISTER => 'onRegisterEntityUsage',
      Events::DELETE_BY_SOURCE_ENTITY => 'onRegisterEntityUsage',
      Events::DELETE_BY_TARGET_ENTITY => 'onRegisterEntityUsage',
    ];
  }

  /**
   * Invalidate the media usage cache tag when a media entity is used.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *   The entity usage event.
   */
  public function onRegisterEntityUsage(EntityUsageEvent $event) {
    if ($event->getTargetEntityId() && $event->getTargetEntityType() == 'media') {
      Cache::invalidateTags(["media_usage:media:{$event->getTargetEntityId()}"]);
    }

    $source_type = $event->getSourceEntityType();
    $source_id = $event->getSourceEntityId();

    if ($source_type && $source_id) {
      Cache::invalidateTags(["media_usage:$source_type:$source_id"]);
    }
  }

}
