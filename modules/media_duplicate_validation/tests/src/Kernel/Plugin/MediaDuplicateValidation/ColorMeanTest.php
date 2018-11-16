<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidation;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean;

/**
 * Class ColorMeanTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean
 *
 * @group media_duplicate_validation
 */
class ColorMeanTest extends KernelTestBase {

  /**
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

  /**
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean
   */
  protected $plugin;

  /**
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity;

  protected static $modules = [
    'system',
    'media_duplicate_validation',
    'media',
    'user',
    'image',
    'file',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');

    $this->duplicationManager = \Drupal::service('plugin.manager.media_duplicate_validation');
    $this->plugin = $this->duplicationManager->createInstance('color_mean');
    $this->duplicationManager->buildPluginSchemas();

    MediaType::create([
      'id' => 'image',
      'label' => 'image',
      'source' => 'image',
      'source_configuration' => ['source_field' => 'field_media_image'],
    ])->save();

    FieldStorageConfig::create([
      'id' => 'media.field_media_image',
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'type' => 'image',
      'module' => 'image',
    ])->save();
    FieldConfig::create([
      'id' => 'media.image.field_media_image',
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'bundle' => 'image',
      'label' => 'Image',
      'field_type' => 'image',
    ])->save();

    $path = 'public://logo.png';
    file_unmanaged_copy(__DIR__ . '/../assets/logo.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $this->mediaEntity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $this->mediaEntity->save();
  }

  /**
   * @covers ::schema
   */
  public function testDatabase() {
    $schema = \Drupal::database()->schema();

    $this->assertTrue($schema->tableExists(ColorMean::DATABASE_TABLE));
    for ($i = 1; $i <= ColorMean::RESIZE_DIMENSION; $i++) {
      $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'column_' . $i));
      $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'row_' . $i));
    }
    $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'mid'));
  }

  /**
   * @covers ::mediaSave
   * @covers ::mediaDelete
   */
  public function testMediaSaveDelete() {
    $mid = $this->mediaEntity->id();

    $this->assertNotEmpty(\Drupal::database()
      ->select(ColorMean::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)->execute()->fetchAssoc());

    $this->mediaEntity->delete();

    $this->assertEmpty(\Drupal::database()
      ->select(ColorMean::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)->execute()->fetchAssoc());
  }

  /**
   * @covers ::populateTable
   */
  public function testPopulateTable() {
    $this->plugin->populateTable();
    $queue = \Drupal::database()
      ->select('queue', 'q')
      ->fields('q')
      ->condition('name', 'media_duplicate_validation')
      ->execute()
      ->fetchAssoc();
    $this->assertNotEmpty($queue);
  }

  /**
   * Test that we get similar items from two images.
   *
   * @covers ::getSimilarItems
   * @covers ::getLikeness
   * @covers ::getCloseMedia
   * @covers ::getColorData
   * @covers ::mimeType
   * @covers ::createImage
   * @covers ::resizeImage
   * @covers ::getColorValues
   * @covers ::getRowColumnAverages
   */
  public function testSimilarItems() {
    $path = 'public://smaller_logo_2.jpg';
    file_unmanaged_copy(__DIR__ . '/../assets/smaller_logo.jpg', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ])->save();

    $path = 'public://smaller_logo.jpg';
    file_unmanaged_copy(__DIR__ . '/../assets/smaller_logo.jpg', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertCount(2, $this->plugin->getSimilarItems($new_entity));
  }

  /**
   *  Test that we get no similar items from two images.
   *
   * @covers ::getSimilarItems
   * @covers ::getLikeness
   * @covers ::getCloseMedia
   * @covers ::getColorData
   * @covers ::mimeType
   * @covers ::createImage
   * @covers ::resizeImage
   * @covers ::getColorValues
   * @covers ::getRowColumnAverages
   */
  public function testDifferentItems() {
    $path = 'public://different_logo.png';
    file_unmanaged_copy(__DIR__ . '/../assets/different_logo.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

  public function testGif() {
    $path = 'public://gif_logo.gif';
    file_unmanaged_copy(__DIR__ . '/../assets/gif_logo.gif', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

  /**
   * @covers ::getSimilarItems
   */
  public function testNonImage() {
    $path = 'public://testfile.txt';
    file_unmanaged_copy(__DIR__ . '/../assets/testfile.txt', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

}
