<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\media\source;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\Image as OriginalImage;
use Drupal\stanford_media\Plugin\media\Source\Image as NewImage;
use Drupal\Tests\UnitTestCase;

/**
 * Class ImageTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\media\Source\Image
 */
class ImageTest extends UnitTestCase {

  /**
   * Drupal dependency container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * If the mock object will return an array with a caption field.
   *
   * @var bool
   */
  protected $returnCaption;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->returnCaption = TRUE;

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type->method('getFieldMap')
      ->will($this->returnCallback([$this, 'getFieldMapCallback']));

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')->willReturn($media_type);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $field_type_manager = $this->createMock(FieldTypePluginManagerInterface::class);
    $config_factory = $this->getConfigFactoryStub([]);

    $image = $this->createMock(ImageInterface::class);
    $image->method('getWidth')->willReturn('bar');

    $image_factory = $this->createMock(ImageFactory::class);
    $image_factory->method('get')->willReturn($image);
    $file_system = $this->createMock(FileSystemInterface::class);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('entity_field.manager', $entity_field_manager);
    $this->container->set('plugin.manager.field.field_type', $field_type_manager);
    $this->container->set('config.factory', $config_factory);
    $this->container->set('image.factory', $image_factory);
    $this->container->set('file_system', $file_system);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);
  }

  /**
   * Test plugin methods compared to original plugin.
   */
  public function testPlugin() {

    $original_image = OriginalImage::create($this->container, ['source_field' => 'foo'], '', []);
    $this->assertArrayNotHasKey('caption', $original_image->getMetadataAttributes());

    $new_image = NewImage::create($this->container, [], '', []);
    $this->assertArrayHasKey('caption', $new_image->getMetadataAttributes());

    $file = $this->createMock(FileInterface::class);

    $entity = $this->createMock(MediaInterface::class);
    $entity->method('get')->willReturn(new FieldListStub($file));

    $this->assertEquals('bar', $new_image->getMetadata($entity, 'width'));
    $this->assertEquals('baz', $new_image->getMetadata($entity, 'caption'));

    $this->returnCaption = FALSE;
    $this->assertNull($new_image->getMetadata($entity, 'caption'));
  }

  /**
   * Mock media type method callback.
   *
   * @return array
   *   Field map settings.
   */
  public function getFieldMapCallback() {
    return $this->returnCaption ? ['caption' => 'field_foo_bar'] : [];
  }

}

/**
 * Stubbed field list.
 */
class FieldListStub {

  /**
   * file entity.
   */
  public $entity;

  /**
   * FieldListStub constructor.
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   * @return string
   */
  public function getString() {
    return 'baz';
  }

}
