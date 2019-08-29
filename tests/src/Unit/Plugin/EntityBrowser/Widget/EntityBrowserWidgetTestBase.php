<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EntityBrowser\Widget;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EntityBrowserWidgetTestBase.
 *
 * @group stanford_media
 */
abstract class EntityBrowserWidgetTestBase extends UnitTestCase {

  /**
   * Widget plugin object.
   *
   * @var mixed
   */
  protected $plugin;

  /**
   * Drupal dependency container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * If the mock duplication service should return any similar entities.
   *
   * @var bool
   */
  protected $returnSimilarItems = FALSE;

  /**
   * If the mock bundle suggestion will return a suggestion.
   *
   * @var bool
   */
  protected $returnBundleSuggestion = TRUE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('create')
      ->willReturn($this->getMockMediaEntity(123));
    $entity_storage->method('load')->willReturn($this->getMockMediaEntity());

    $entity_view_builder = $this->createMock(EntityViewBuilderInterface::class);
    $entity_view_builder->method('view')->willReturn([]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);
    $entity_type_manager->method('getViewBuilder')
      ->willReturn($entity_view_builder);

    $widget_validation = $this->createMock(WidgetValidationManager::class);

    $bundle_suggestion = $this->createMock(BundleSuggestionManagerInterface::class);
    $bundle_suggestion->method('getSuggestedBundle')
      ->will($this->returnCallback([$this, 'getSuggestedBundleCallback']));
    $bundle_suggestion->method('getAllExtensions')->willReturn(['php', 'jpg']);
    $bundle_suggestion->method('getMultipleBundleExtensions')
      ->willReturn(['php', 'jpg']);
    $bundle_suggestion->method('getMaxFileSize')->willReturn(rand(100, 10000));

    $current_user = $this->createMock(AccountProxyInterface::class);

    $messenger = $this->createMock(MessengerInterface::class);

    $this->container = new ContainerBuilder();
    $this->container->set('event_dispatcher', $event_dispatcher);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('plugin.manager.entity_browser.widget_validation', $widget_validation);
    $this->container->set('plugin.manager.bundle_suggestion_manager', $bundle_suggestion);
    $this->container->set('current_user', $current_user);
    $this->container->set('messenger', $messenger);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);
  }

  /**
   * Add the media duplication service mock object to the container.
   *
   * Make sure the tests pass with and without the duplication service.
   */
  protected function addDuplicationValidationService() {
    $duplication_validation = $this->createMock(MediaDuplicateValidationManager::class);
    $duplication_validation->method('getSimilarEntities')
      ->will($this->returnCallback([$this, 'getSimilarEntitiesCallback']));

    $this->container->set('plugin.manager.media_duplicate_validation', $duplication_validation);
    \Drupal::getContainer()
      ->set('plugin.manager.media_duplicate_validation', $duplication_validation);
  }

  /**
   * Get a mock media entity.
   *
   * @param null $entityId
   *   Optional set what the entity id should be on the mock object.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   Mock media entity.
   */
  protected function getMockMediaEntity($entityId = NULL) {
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('id')->willReturn($entityId ?: rand(1, 100));
    return $entity;
  }

  /**
   * Get a suggested media type for the bundle suggestion service.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|void
   *   Mock media type.
   */
  public function getSuggestedBundleCallback() {
    if (!$this->returnBundleSuggestion) {
      return;
    }

    $media_source = $this->createMock(MediaSourceInterface::class);
    $media_source->method('getConfiguration')
      ->willReturn(['source_field' => 'field_foo']);

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getSource')->willReturn($media_source);

    return $media_type;
  }

}
