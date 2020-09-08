<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter;

/**
 * Class EmbeddableFormatterTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter
 */
class EmbeddableFormatterTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'image',
    'media',
    'path_alias',
    'stanford_media',
    'field',
    'file',
    'entity_test',
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * Google Form media type bundle.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  protected $mediaType;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    /*
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('file');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_view_display');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

    $this->mediaType = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $this->mediaType->save();
    $source_field = $this->mediaType->getSource()->createSourceField($this->mediaType);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $this->mediaType
      ->set('source_configuration', [
        'source_field' => $source_field->getName(),
      ])
      ->save();

    $this->media = Media::create([
      'bundle' => 'embeddable',
      'field_media_embeddable' => 'http://google.com/forms/a/b/formid/viewform',
    ]);
    $this->media->save();

    $display_options = [
      'type' => 'embeddable_formatter',
    ];
    */

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */

    /*
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'embeddable',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->setComponent($source_field->getName(), $display_options)
      ->removeComponent('thumbnail')
      ->save();
    */
  }

    public function testNonMediaField() {
      /*
      EntityTestBundle::create(['id' => 'test'])->save();

      $field_storage = FieldStorageConfig::create([
        'type' => 'entity_reference',
        'field_name' => 'field_test_media',
        'entity_type' => 'entity_test',
        'settings' => [
          'target_type' => 'media',
        ],
      ]);
      $field_storage->save();

      $field_config = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => 'test',
      ]);
      $field_config->save();

      $this->assertFalse(EmbeddableFormatter::isApplicable($field_config));
      */
    }

    public function testOtherMediaTypeField() {
      /*
      $mediaType = MediaType::create([
        'id' => 'video',
        'label' => 'video',
        'source' => 'oembed:video',
      ]);
      $mediaType->save();
      $source_field = $mediaType->getSource()->createSourceField($mediaType);

      $this->assertFalse(EmbeddableFormatter::isApplicable($source_field));
      */
    }

  public function testEmbeddableatter() {
    /*
    $source_field = $this->media->getSource()->getSourceFieldDefinition($this->mediaType);
    $this->assertTrue(EmbeddableFormatter::isApplicable($source_field));

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
    $display = $view_builder->view($this->media, 'default');
    $display = \Drupal::service('renderer')->renderPlain($display);
    preg_match('/<iframe.*src="http:\/\/google.com\/forms\/a\/b\/formid\/viewform"/', $display, $matches);
    $this->assertCount(1, $matches);
    */
  }

}
