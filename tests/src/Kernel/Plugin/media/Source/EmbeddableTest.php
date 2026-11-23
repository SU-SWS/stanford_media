<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\media\Source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Form\FormState;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the embeddable media source.
 */
#[Group('stanford_media')]
#[RunTestsInSeparateProcesses]
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
    'path_alias',
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $oembedMedia;

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $unstructuredMedia;

  /**
   * The Media Type.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  protected $mediaType;

  /**
   * A test embed string.
   *
   * @var string
   */
  protected $iframeCode = '<iframe src="http://www.test.com"></iframe>';

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
    $this->installEntitySchema('path_alias');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

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

    // Set up media instances.
    // We have to test this with a null value.
    // Otherwise, the outbound http request fails
    // see also: https://www.drupal.org/project/drupal/issues/2571475
    $this->oembedMedia = Media::create([
      'bundle' => 'embeddable',
      'name' => 'oembed embeddable',
      'field_media_embeddable_oembed' => NULL,
    ]);
    $this->oembedMedia->save();

    $this->unstructuredMedia = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => $this->iframeCode,
    ]);
    $this->unstructuredMedia->save();
  }

  /**
   * Test methods on the embeddable source.
   */
  public function testEmbeddableSource() {
    $oembed_media_source = $this->oembedMedia->getSource();
    $this->assertEquals(NULL, $oembed_media_source->getMetadata($this->oembedMedia, 'url'));
    $this->assertEquals(NULL, $oembed_media_source->getSourceFieldValue($this->oembedMedia));
    $this->assertFalse($oembed_media_source->hasUnstructured($this->oembedMedia));
    $this->assertCount(15, $oembed_media_source->getMetadataAttributes());
    $this->assertArrayHasKey('embeddable', $oembed_media_source->getSourceFieldConstraints());

    $unstructured_media_source = $this->unstructuredMedia->getSource();
    $this->assertEquals($this->iframeCode, $unstructured_media_source->getSourceFieldValue($this->unstructuredMedia));
    $this->assertTrue($unstructured_media_source->hasUnstructured($this->unstructuredMedia));
    $this->assertCount(15, $unstructured_media_source->getMetadataAttributes());
    $this->assertNotNull($unstructured_media_source->getMetadata($this->unstructuredMedia, 'title'));
    $this->assertStringContainsString('iframe src', $unstructured_media_source->getSourceFieldValue($this->unstructuredMedia));
  }

  /**
   * Tests the configuration form.
   */
  public function testBuildConfigurationForm() {
    $form_state = new FormState();
    $form = [];
    $source = $this->unstructuredMedia->getSource();
    $form_array = $source->buildConfigurationForm($form, $form_state);
    $this->assertArrayHasKey('source_field', $form_array);
    $this->assertArrayHasKey('unstructured_field_name', $form_array);
  }

  /**
   * When restricted, the embed code should not be allowed.
   */
  public function testAllowedEmbeds() {
    /** @var \Drupal\stanford_media\Plugin\media\Source\EmbeddableInterface $source */
    $source = $this->unstructuredMedia->getSource();
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
