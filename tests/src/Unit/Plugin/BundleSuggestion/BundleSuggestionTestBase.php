<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\BundleSuggestion;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class BundleSuggestionTestBase
 *
 * @package Drupal\Tests\stanford_media\Unit\Plugin\BundleSuggestion
 */
abstract class BundleSuggestionTestBase extends UnitTestCase {

  /**
   * Container object.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $media_source = $this->createMock(MediaSourceInterface::class);
    $media_source->method('getConfiguration')
      ->willReturn(['source_field' => 'field_foo']);

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getSource')->willReturn($media_source);

    $field_type = $this->createMock(FieldConfigInterface::class);
    $field_type->method('getSetting')->will($this->returnCallback([
      $this,
      'fieldGetSetting',
    ]));
    $field_type->method('getType')->will($this->returnCallback([
      $this,
      'fieldGetTypeCallback',
    ]));

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('loadMultiple')
      ->willReturn(['foo' => $media_type]);
    $entity_storage->method('load')->willReturn($field_type);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $file_system = $this->createMock(FileSystemInterface::class);
    $file_system->method('uriScheme')->will($this->returnCallback([
      $this,
      'fileSystemUriSchemeCallback',
    ]));

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('file_system', $file_system);
  }

  public function fileSystemUriSchemeCallback($input) {
    if (preg_match('/^([\w\-]+):\/\/|^(data):/', $input, $matches)) {
      // The scheme will always be the last element in the matches array.
      return array_pop($matches);
    }

    return FALSE;
  }

  public function fieldGetSetting($setting) {
    if ($setting == 'file_extensions') {
      return 'foo';
    }
    return ['foo'];
  }

  /**
   * Field type setting callback.
   *
   * @return string
   *   Field machine type.
   */
  abstract public function fieldGetTypeCallback();

}
