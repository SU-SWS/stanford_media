<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_field\ProviderManager as VideoProviderManager;
use Drupal\video_embed_field\ProviderPluginInterface as VideoProviderPluginInterface;

/**
 * Class MediaEmbedDialogTestBase.
 *
 * @group stanford_media
 */
abstract class MediaEmbedDialogTestBase extends UnitTestCase {

  /**
   * Mock dependency container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Mock media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity;

  /**
   * What media bundle the entity should be.
   *
   * @var string
   */
  protected $mediaBundle;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $video_plugin = $this->createMock(VideoProviderPluginInterface::class);
    $video_plugin->method('getPluginId')->willReturn('foo');

    $video_provider = $this->createMock(VideoProviderManager::class);
    $video_provider->method('loadProviderFromInput')->willReturn($video_plugin);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')
      ->will($this->returnCallback([$this, 'loadCallback']));
    $entity_storage->method('loadMultiple')
      ->will($this->returnCallback([$this, 'loadMultipleCallback']));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('video_embed_field.provider_manager', $video_provider);
    $this->container->set('config.factory', $this->getConfigFactoryStub([
      'stanford_media.settings' => [
        'embeddable_image_styles' => [],
        'allowed_caption_formats' => ['minimal_html'],
      ],
    ]));
    $this->container->set('logger.factory', $this->createMock(LoggerChannelFactoryInterface::class));
    $this->container->set('path.validator', $this->createMock(PathValidatorInterface::class));
    \Drupal::setContainer($this->container);

    $media_source = $this->createMock(MediaSourceInterface::class);
    $media_source->method('getConfiguration')
      ->willReturn(['source_field' => 'field_foo']);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('getString')
      ->will($this->returnCallback([$this, 'fieldGetStringCallback']));

    $this->mediaEntity = $this->createMock(MediaInterface::class);
    $this->mediaEntity->method('bundle')
      ->will($this->returnCallback([$this, 'mediaBundleCallback']));
    $this->mediaEntity->method('getSource')->willReturn($media_source);
    $this->mediaEntity->method('get')->willReturn($field_list);
  }

  /**
   * Media bundle callback.
   *
   * @return string
   *   What media bundle the mock entity is.
   */
  public function mediaBundleCallback() {
    return $this->mediaBundle;
  }

}
