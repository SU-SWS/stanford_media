<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Base test class.
 */
abstract class MediaDuplicateValidationTestBase extends KernelTestBase {

  /**
   * Duplication manager service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

  /**
   * Created media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity;

  /**
   * {@inheritdoc}
   */
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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');

    $this->duplicationManager = \Drupal::service('plugin.manager.media_duplicate_validation');
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
    copy(__DIR__ . '/assets/logo.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $this->mediaEntity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $this->mediaEntity->save();

    $path = 'public://logo2.png';
    copy(__DIR__ . '/assets/logo.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ])->save();
  }

}
