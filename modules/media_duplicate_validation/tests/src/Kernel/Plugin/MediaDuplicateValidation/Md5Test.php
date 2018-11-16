<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidation;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Class ColorMeanTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5
 *
 * @group media_duplicate_validation
 */
class Md5Test extends KernelTestBase {

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
    $this->plugin = $this->duplicationManager->createInstance('md5');
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
    $this->assertTrue($schema->tableExists(Md5::DATABASE_TABLE));
    $this->assertTrue($schema->fieldExists(Md5::DATABASE_TABLE, 'mid'));
    $this->assertTrue($schema->fieldExists(Md5::DATABASE_TABLE, 'md5'));
  }

  /**
   * @covers ::mediaSave
   * @covers ::mediaDelete
   */
  public function testMediaSaveDelete() {
    $mid = $this->mediaEntity->id();
    $this->assertNotEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)
      ->execute()
      ->fetchAssoc());
    $this->mediaEntity->delete();
    $this->assertEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)
      ->execute()
      ->fetchAssoc());
  }

  /**
   * @covers ::getSimilarItems
   */
  public function testSimilarItems() {
    $path = 'public://logo.png';
    file_unmanaged_copy(__DIR__ . '/../assets/logo2.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ])->save();
    $this->assertNotEmpty($this->plugin->getSimilarItems($this->mediaEntity));
  }

}
