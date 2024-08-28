<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Plugin\BundleSuggestionInterface;
use Drupal\stanford_media\Plugin\BundleSuggestionManager;
use Drupal\Tests\UnitTestCase;

/**
 * Class BundleSuggestionManagerTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\BundleSuggestionManager
 */
class BundleSuggestionManagerTest extends UnitTestCase {

  /**
   * Plugin manager.
   *
   * @var \Drupal\stanford_media\Plugin\BundleSuggestionManager
   */
  protected $suggestionManager;

  /**
   * Field uri scheme setting.
   *
   * @var string
   */
  protected $fieldUriScheme = 'public';

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $namespaces = $this->createMock(\Traversable::class);
    $cache = $this->getCacheStub();
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $field_manager->method('getFieldMapByFieldType')->willReturn([TRUE]);

    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getConfiguration')
      ->willReturn(['source_field' => $this->randomMachineName()]);

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getSource')->willReturn($source);

    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('getSetting')->willReturnCallback([
      $this,
      'getFieldSettingCallback',
    ]);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('loadMultiple')->willReturn(['foo' => $media_type]);
    $entity_storage->method('load')->willReturn($field_config);

    $config_factory = $this->getConfigFactoryStub(['system.file' => ['default_scheme' => 'public']]);

    $media = $this->createMock(MediaInterface::class);
    $media->method('access')->willReturn(TRUE);
    $entity_storage->method('create')->willReturn($media);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $this->suggestionManager = new BundleSuggestionManagerOverride($namespaces, $cache, $module_handler, $field_manager, $entity_type_manager, $config_factory);
    $this->suggestionManager->setMediaType($media_type);
  }

  /**
   * Field config setting callback on mock object.
   *
   * @param string $setting
   *   Field setting.
   *
   * @return string
   *   Setting value.
   */
  public function getFieldSettingCallback($setting) {
    switch ($setting) {
      case 'max_filesize':
        return '2MB';

      case 'file_directory':
        return 'foo/bar/baz';

      case 'file_extensions':
        return 'jpg png jpeg';

      case 'uri_scheme':
        return $this->fieldUriScheme;
    }
  }

  /**
   * Test the manager gets a bundle suggestion plugin..
   */
  public function testSuggestedBundle() {
    $this->assertInstanceOf('\Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface', $this->suggestionManager);
    $this->assertArrayHasKey('foo', $this->suggestionManager->getDefinitions());

    $this->assertInstanceOf(MediaTypeInterface::class, $this->suggestionManager->getSuggestedBundle('foo'));
    $this->assertNull($this->suggestionManager->getSuggestedBundle('bar'));
  }

  /**
   * Test the names of a valid and invalid input.
   */
  public function testSuggestedName() {
    $this->assertEquals('foo', $this->suggestionManager->getSuggestedName('foo'));
    $this->assertNull($this->suggestionManager->getSuggestedName('bar'));
  }

  /**
   * An array of extensions can be uploaded.
   */
  public function testExtensions() {
    $this->assertEquals([
      'jpg',
      'png',
      'jpeg',
    ], $this->suggestionManager->getAllExtensions());
  }

  /**
   * File size should be the correct number.
   */
  public function testFilesize() {
    $this->assertEquals(2097152, $this->suggestionManager->getMaxFileSize());
  }

  /**
   * Verify upload path comes back appropriately.
   */
  public function testUploadPath() {
    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getConfiguration')
      ->willReturn(['source_field' => $this->randomMachineName()]);

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getSource')->willReturn($source);

    $this->assertEquals('public://foo/bar/baz/', $this->suggestionManager->getUploadPath($media_type));
  }

  /**
   * Verify upload path comes back appropriately.
   */
  public function testPrivateUploadPath() {
    $this->fieldUriScheme = 'private';
    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getConfiguration')
      ->willReturn(['source_field' => $this->randomMachineName()]);

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getSource')->willReturn($source);

    $this->assertEquals('private://foo/bar/baz/', $this->suggestionManager->getUploadPath($media_type));
  }

  /**
   * Get a mocked cache object.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   */
  protected function getCacheStub() {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache_data = new \stdClass();
    $cache_data->data = [
      'foo' => [
        'field_types' => ['image', 'file'],
        'id' => 'foo',
        'provider' => 'stanford_media',
      ],
    ];
    $cache->method('get')->willReturn($cache_data);
    return $cache;
  }

}

/**
 * Override manager to return a fake bundle suggestion plugin.
 *
 * @package Drupal\Tests\stanford_media\Unit\Plugin
 */
class BundleSuggestionManagerOverride extends BundleSuggestionManager {

  protected $mediaType;

  public function createInstance($plugin_id, array $configuration = []) {
    return new BundleSuggestionPluginTest($this->mediaType);
  }

  public function setMediaType(MediaTypeInterface $mediaType) {
    $this->mediaType = $mediaType;
  }

}

/**
 * Fake bundle suggestion plugin.
 *
 * @package Drupal\Tests\stanford_media\Unit\Plugin
 */
class BundleSuggestionPluginTest implements BundleSuggestionInterface {

  public function __construct(protected MediaTypeInterface $mediaType) {
  }

  public function getBundleFromString(string $input): ?MediaTypeInterface {
    return $input == 'foo' ? $this->mediaType : NULL;
  }

  public function getName($input): ?string {
    return $input == 'foo' ? 'foo' : NULL;
  }

}
