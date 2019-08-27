<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\media\Source;

use Drupal\audio_embed_field\Plugin\audio_embed_field\Provider\SoundCloud;
use Drupal\audio_embed_field\ProviderPluginInterface as AudioProviderPluginInterface;
use Drupal\audio_embed_field\ProviderManager as AudioProviderManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class MediaSourcePluginTestBase.
 *
 * @group stanford_media
 */
abstract class MediaSourcePluginTestBase extends UnitTestCase {

  /**
   * Drupal dependency container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getFieldMap')
      ->will($this->returnCallback([$this, 'getFieldMapCallback']));

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')->willReturn($media_type);
    $entity_storage->method('create')
      ->willReturn($this->createMock(FieldConfigInterface::class));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $field_storage = $this->createMock(FieldStorageConfigInterface::class);

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldDefinitions')
      ->will($this->returnCallback([$this, 'getFieldDefinitionsCallback']));
    $entity_field_manager->method('getFieldStorageDefinitions')
      ->willReturn(['foo' => $field_storage]);

    $field_type_manager = $this->createMock(FieldTypePluginManagerInterface::class);
    $config_factory = $this->getConfigFactoryStub([]);

    $image = $this->createMock(ImageInterface::class);
    $image->method('getWidth')->willReturn('bar');

    $image_factory = $this->createMock(ImageFactory::class);
    $image_factory->method('get')->willReturn($image);
    $file_system = $this->createMock(FileSystemInterface::class);

    $audio_plugin = $this->createMock(AudioProviderPluginInterface::class);
    $audio_plugin->method('getName')->willReturn('foo bar baz');
    $audio_plugin->method('getIdFromInput')->willReturn('foo-baz');

    $audio_provider = $this->createMock(AudioProviderManager::class);
    $audio_provider->method('loadProviderFromInput')
      ->willReturn(new AudioProviderPluginTest());
    $audio_provider->method('loadDefinitionFromInput')
      ->willReturn(['id' => 'foo_bar']);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('entity_field.manager', $entity_field_manager);
    $this->container->set('plugin.manager.field.field_type', $field_type_manager);
    $this->container->set('config.factory', $config_factory);
    $this->container->set('image.factory', $image_factory);
    $this->container->set('file_system', $file_system);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('audio_embed_field.provider_manager', $audio_provider);
    \Drupal::setContainer($this->container);
  }

}

class AudioProviderPluginTest extends SoundCloud {

  public function __construct() {
  }

  public function getName() {
    return 'foo bar baz';
  }

  public static function getIdFromInput($input) {
    return 'foo-baz';
  }

  public function downloadThumbnail() {

  }

  public function getLocalThumbnailUri() {
    return __FILE__;
  }

}
