<?php

namespace Drupal\Tests\stanford_media\Unit\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Drupal\entity_usage\Events\Events;
use Drupal\stanford_media\EventSubscriber\StanfordMediaSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the Stanford Media event subscriber.
 */
#[Group('stanford_media')]
class StanfordMediaSubscriberTest extends UnitTestCase {

  /**
   * The event subscriber under test.
   *
   * @var \Drupal\stanford_media\EventSubscriber\StanfordMediaSubscriber
   */
  protected $subscriber;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a container with cache tags invalidator.
    $container = new ContainerBuilder();
    $this->cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);

    $this->subscriber = new StanfordMediaSubscriber();
  }

  /**
   * Test that the subscriber subscribes to the correct events.
   */
  public function testGetSubscribedEvents() {
    $events = StanfordMediaSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey(Events::USAGE_REGISTER, $events);
    $this->assertArrayHasKey(Events::DELETE_BY_SOURCE_ENTITY, $events);
    $this->assertArrayHasKey(Events::DELETE_BY_TARGET_ENTITY, $events);
    $this->assertEquals('onRegisterEntityUsage', $events[Events::USAGE_REGISTER]);
    $this->assertEquals('onRegisterEntityUsage', $events[Events::DELETE_BY_SOURCE_ENTITY]);
    $this->assertEquals('onRegisterEntityUsage', $events[Events::DELETE_BY_TARGET_ENTITY]);
  }

  /**
   * Test cache invalidation for media entity usage.
   */
  public function testOnRegisterEntityUsageWithMediaTarget() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(123);
    $event->method('getTargetEntityType')->willReturn('media');
    $event->method('getSourceEntityType')->willReturn('node');
    $event->method('getSourceEntityId')->willReturn(456);

    // Expect both media and source cache tags to be invalidated.
    $this->cacheTagsInvalidator->expects($this->exactly(2))
      ->method('invalidateTags')
      ->willReturnCallback(function($tags) {
        static $callCount = 0;
        $callCount++;
        if ($callCount === 1) {
          $this->assertEquals(['media_usage:media:123'], $tags);
        }
        elseif ($callCount === 2) {
          $this->assertEquals(['media_usage:node:456'], $tags);
        }
      });

    $this->subscriber->onRegisterEntityUsage($event);
  }

  /**
   * Test cache invalidation when target is not media.
   */
  public function testOnRegisterEntityUsageWithNonMediaTarget() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(123);
    $event->method('getTargetEntityType')->willReturn('node');
    $event->method('getSourceEntityType')->willReturn('paragraph');
    $event->method('getSourceEntityId')->willReturn(789);

    // Only source cache tag should be invalidated since target is not media.
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['media_usage:paragraph:789']);

    $this->subscriber->onRegisterEntityUsage($event);
  }

  /**
   * Test cache invalidation when target entity ID is null.
   */
  public function testOnRegisterEntityUsageWithNullTargetId() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(NULL);
    $event->method('getTargetEntityType')->willReturn('media');
    $event->method('getSourceEntityType')->willReturn('node');
    $event->method('getSourceEntityId')->willReturn(456);

    // Only source cache tag should be invalidated since target ID is null.
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['media_usage:node:456']);

    $this->subscriber->onRegisterEntityUsage($event);
  }

  /**
   * Test cache invalidation when source entity info is missing.
   */
  public function testOnRegisterEntityUsageWithMissingSourceInfo() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(123);
    $event->method('getTargetEntityType')->willReturn('media');
    $event->method('getSourceEntityType')->willReturn(NULL);
    $event->method('getSourceEntityId')->willReturn(NULL);

    // Only media cache tag should be invalidated since source info is missing.
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['media_usage:media:123']);

    $this->subscriber->onRegisterEntityUsage($event);
  }

  /**
   * Test cache invalidation with only source entity type.
   */
  public function testOnRegisterEntityUsageWithSourceTypeOnly() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(NULL);
    $event->method('getTargetEntityType')->willReturn('node');
    $event->method('getSourceEntityType')->willReturn('paragraph');
    $event->method('getSourceEntityId')->willReturn(NULL);

    // No cache tags should be invalidated since source ID is missing.
    $this->cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');

    $this->subscriber->onRegisterEntityUsage($event);
  }

  /**
   * Test cache invalidation with only source entity ID.
   */
  public function testOnRegisterEntityUsageWithSourceIdOnly() {
    $event = $this->createMock(EntityUsageEvent::class);
    $event->method('getTargetEntityId')->willReturn(NULL);
    $event->method('getTargetEntityType')->willReturn('node');
    $event->method('getSourceEntityType')->willReturn(NULL);
    $event->method('getSourceEntityId')->willReturn(789);

    // No cache tags should be invalidated since source type is missing.
    $this->cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');

    $this->subscriber->onRegisterEntityUsage($event);
  }

}
