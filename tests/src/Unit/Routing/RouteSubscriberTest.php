<?php

namespace Drupal\Tests\stanford_media\Unit\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\stanford_media\Controller\MediaAdd;
use Drupal\stanford_media\Routing\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Test the Stanford Media route subscriber.
 */
#[Group('stanford_media')]
class RouteSubscriberTest extends UnitTestCase {

  /**
   * Test that media add page route controller is altered.
   */
  public function testMediaAddPageRouteAltered() {
    $route_collection = new RouteCollection();
    $route = new Route('/media/add');
    $route->setDefault('_controller', 'OriginalController::method');
    $route_collection->add('entity.media.add_page', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(FALSE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $altered_route = $route_collection->get('entity.media.add_page');
    $this->assertEquals(
      MediaAdd::class . '::addPage',
      $altered_route->getDefault('_controller')
    );
  }

  /**
   * Test that canonical route is altered when standalone URL is enabled.
   */
  public function testMediaCanonicalRouteAlteredWithStandaloneUrl() {
    $route_collection = new RouteCollection();
    $route = new Route('/media/{media}');
    $route->setRequirement('_entity_access', 'media.view');
    $route_collection->add('entity.media.canonical', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(TRUE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $altered_route = $route_collection->get('entity.media.canonical');
    $this->assertEquals(
      'media.view_standalone',
      $altered_route->getRequirement('_entity_access')
    );
  }

  /**
   * Test that canonical route is not altered when standalone URL is disabled.
   */
  public function testMediaCanonicalRouteNotAlteredWithoutStandaloneUrl() {
    $route_collection = new RouteCollection();
    $route = new Route('/media/{media}');
    $route->setRequirement('_entity_access', 'media.view');
    $route_collection->add('entity.media.canonical', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(FALSE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $altered_route = $route_collection->get('entity.media.canonical');
    $this->assertEquals(
      'media.view',
      $altered_route->getRequirement('_entity_access')
    );
  }

  /**
   * Test that both routes can be altered together.
   */
  public function testBothRoutesAltered() {
    $route_collection = new RouteCollection();

    $add_route = new Route('/media/add');
    $add_route->setDefault('_controller', 'OriginalController::method');
    $route_collection->add('entity.media.add_page', $add_route);

    $canonical_route = new Route('/media/{media}');
    $canonical_route->setRequirement('_entity_access', 'media.view');
    $route_collection->add('entity.media.canonical', $canonical_route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(TRUE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $this->assertEquals(
      MediaAdd::class . '::addPage',
      $route_collection->get('entity.media.add_page')->getDefault('_controller')
    );

    $this->assertEquals(
      'media.view_standalone',
      $route_collection->get('entity.media.canonical')
        ->getRequirement('_entity_access')
    );
  }

  /**
   * Test when routes don't exist in collection.
   */
  public function testMissingRoutes() {
    $route_collection = new RouteCollection();
    $route = new Route('/some/other/route');
    $route_collection->add('some.other.route', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(TRUE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    // No errors should occur and the collection should be unchanged.
    $this->assertNull($route_collection->get('entity.media.add_page'));
    $this->assertNull($route_collection->get('entity.media.canonical'));
    $this->assertEquals('/some/other/route', $route_collection->get('some.other.route')
      ->getPath());
  }

  /**
   * Test when only add page route exists.
   */
  public function testOnlyAddPageRouteExists() {
    $route_collection = new RouteCollection();
    $route = new Route('/media/add');
    $route->setDefault('_controller', 'OriginalController::method');
    $route_collection->add('entity.media.add_page', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(TRUE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $this->assertEquals(
      MediaAdd::class . '::addPage',
      $route_collection->get('entity.media.add_page')->getDefault('_controller')
    );
    $this->assertNull($route_collection->get('entity.media.canonical'));
  }

  /**
   * Test when only canonical route exists.
   */
  public function testOnlyCanonicalRouteExists() {
    $route_collection = new RouteCollection();
    $route = new Route('/media/{media}');
    $route->setRequirement('_entity_access', 'media.view');
    $route_collection->add('entity.media.canonical', $route);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('standalone_url')->willReturn(TRUE);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('media.settings')
      ->willReturn($config);

    $subscriber = new TestRouteSubscriber($config_factory);
    $subscriber->alterRoutes($route_collection);

    $this->assertEquals(
      'media.view_standalone',
      $route_collection->get('entity.media.canonical')
        ->getRequirement('_entity_access')
    );
    $this->assertNull($route_collection->get('entity.media.add_page'));
  }

}

/**
 * Testable route subscriber with public alterRoutes method.
 */
class TestRouteSubscriber extends RouteSubscriber {

  /**
   * Make alterRoutes public for testing.
   */
  public function alterRoutes(RouteCollection $collection): void {
    parent::alterRoutes($collection);
  }

}
