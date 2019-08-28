<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
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

    $this->container = new ContainerBuilder();
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('video_embed_field.provider_manager', $video_provider);
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

  public function testExample() {
    $this->assertTrue(TRUE);
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
