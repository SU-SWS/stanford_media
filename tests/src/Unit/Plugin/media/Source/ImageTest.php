<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\media\Source;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image as OriginalImage;
use Drupal\stanford_media\Plugin\media\Source\Image as NewImage;

/**
 * Class ImageTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\media\Source\Image
 */
class ImageTest extends MediaSourcePluginTestBase {

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
