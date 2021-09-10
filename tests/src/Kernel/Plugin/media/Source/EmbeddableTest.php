<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\media\Source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Form\FormState;

/**
 * Class EmbeddableTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\media\Source\Embeddable
 */
class EmbeddableTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'image',
    'media',
    'path_alias',
    'field',
    'file',
    'stanford_media',
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
   * The Media Type.
   *
   * @var \Drupal\media\entity\MediaType
   */
  protected $media_type;

  /**
   * A test embed string.
   *
   * @var string
   */
  protected $iframe_code = '<iframe src="http://www.test.com"></iframe>';

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {

    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

    $this->media_type = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $this->media_type->save();

    $this->media_type
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
      'field_media_embeddable_oembed' => NULL,
    ]);
    $this->oembed_media->save();

    $this->unstructured_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => $this->iframe_code,
    ]);
    $this->unstructured_media->save();

  }

  /**
   * Test methods on the embeddable source.
   *
   * @covers ::getMetadata
   * @covers ::getUnstructuredMetadata
   * @covers ::getMetadata
   * @covers ::getSourceFieldConstraints
   * @covers ::getSourceFieldValue
   * @covers ::hasUnstructured
   *
   */
  public function testEmbeddableSource() {
    $oembed_media_source = $this->oembed_media->getSource();
    $this->assertEquals(NULL, $oembed_media_source->getMetadata($this->oembed_media, 'url'));
    $this->assertEquals(NULL, $oembed_media_source->getSourceFieldValue($this->oembed_media));
    $this->assertFalse($oembed_media_source->hasUnstructured($this->oembed_media));
    $this->assertCount(15, $oembed_media_source->getMetadataAttributes());
    $this->assertArrayHasKey('embeddable', $oembed_media_source->getSourceFieldConstraints());

    $unstructured_media_source = $this->unstructured_media->getSource();
    $this->assertEquals($this->iframe_code, $unstructured_media_source->getSourceFieldValue($this->unstructured_media));
    $this->assertTrue($unstructured_media_source->hasUnstructured($this->unstructured_media));
    $this->assertCount(15, $unstructured_media_source->getMetadataAttributes());
    $this->assertNotNull($unstructured_media_source->getMetadata($this->unstructured_media, 'title'));
    $this->assertStringContainsString('iframe src', $unstructured_media_source->getSourceFieldValue($this->unstructured_media));
  }


  /**
   * Tests the configuration form.
   *
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form_state = new FormState();
    $form = [];
    $source = $this->unstructured_media->getSource();
    $form_array = $source->buildConfigurationForm($form, $form_state);
    $this->assertArrayHasKey('source_field', $form_array);
    $this->assertArrayHasKey('unstructured_field_name', $form_array);
  }

  /**
   * When restricted, the embed code should not be allowed.
   */
  public function testAllowedEmbeds() {
    /** @var \Drupal\stanford_media\Plugin\media\Source\EmbeddableInterface $source */
    $source = $this->unstructured_media->getSource();
    $this->assertTrue($source->embedCodeIsAllowed('<script src="foo.bar"></script>'));

    $config = $source->getConfiguration();
    $config['embed_validation'] = ['localist'];
    $source->setConfiguration($config);
    $this->assertFalse($source->embedCodeIsAllowed('<script src="foo.bar"></script>'));

    $div = '<div id="localist-widget-1234"></div>';
    $script = '<script src="stanford.enterprise.localist.com"></script>';
    $this->assertTrue($source->embedCodeIsAllowed("$div$script"));

    $this->assertEquals("$div\n$script", $source->prepareEmbedCode("$div$script<a href='#'>Foo</a>"));
  }

}
