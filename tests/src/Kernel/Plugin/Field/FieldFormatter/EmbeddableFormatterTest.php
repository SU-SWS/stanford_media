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
    'field_permissions',
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $oembed_media;

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $unstructured_media;

  /**
   * A test embed string.
   *
   * @var string
   */
  protected $iframe_code = '<iframe src="http://www.test.com"></iframe>';
  /**
   * Embeddable media type bundle.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  protected $mediaType;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');
    $this->installConfig('field_permissions');

    $this->mediaType = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $this->mediaType->save();

    $this->mediaType
      ->set('source_configuration', [
        'oembed_field_name' => 'field_media_embeddable_oembed',
        'unstructured_field_name' => 'field_media_embeddable_code',
        'thumbnails_directory' => 'public://oembed_thumbnails',
        'source_field' => 'field_media_embeddable_oembed',
      ])
      ->save();

      // Create the fields we need.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_oembed',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'oembed',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_code',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'unstructured',
    ])->save();

    // set up media instances.

    // We have to test this with a null value.
    // Otherwise, the outbound http request fails
    // see also: https://www.drupal.org/project/drupal/issues/2571475
    $this->oembed_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'oembed embeddable',
      'field_media_embeddable_oembed' => '',
    ]);
    $this->oembed_media->save();


    $this->unstructured_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => $this->iframe_code,
    ]);
    $this->unstructured_media->save();

    $display_options = [
      'type' => 'embeddable_formatter',
    ];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */


    $display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'embeddable',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->setComponent('field_media_embeddable_code', $display_options)
      ->removeComponent('thumbnail')
      ->save();

  }

    public function testNonMediaField() {

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

    }

    public function testOtherMediaTypeField() {

      $mediaType = MediaType::create([
        'id' => 'video',
        'label' => 'video',
        'source' => 'oembed:video',
      ]);
      $mediaType->save();
      $source_field = $mediaType->getSource()->createSourceField($mediaType);

      $this->assertFalse(EmbeddableFormatter::isApplicable($source_field));

    }
  /**
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::isApplicable
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewElements
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewUnstructuredElements
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewOEmbedElements
   */
  public function testEmbeddableFormatter() {
    $source_field = $this->oembed_media->getSource()->getSourceFieldDefinition($this->mediaType);
    $this->assertTrue(EmbeddableFormatter::isApplicable($source_field));
    $source_field = $this->unstructured_media->getSource()->getSourceFieldDefinition($this->mediaType);
    $this->assertTrue(EmbeddableFormatter::isApplicable($source_field));


    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('media');
    $view_render = $view_builder->view($this->unstructured_media, 'default');
    $rendered_view = \Drupal::service('renderer')->renderPlain($view_render);
    $this->assertStringContainsString('<div class="embeddable-content"><iframe src="http://www.test.com">', $rendered_view);

    // Kernel tests don't let us make remote http requests, so embeds will come back empty.
    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('media');
    $view_render = $view_builder->view($this->oembed_media, 'default');
    $rendered_view = \Drupal::service('renderer')->renderPlain($view_render);
    $this->assertStringContainsString('<div>', $rendered_view);

  }

}
