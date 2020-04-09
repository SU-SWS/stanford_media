<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\media\Source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Class GoogleFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\media\Source\GoogleForm
 */
class GoogleFormTest extends KernelTestBase {

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
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

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

    $media_type = MediaType::create([
      'id' => 'google_form',
      'label' => 'google_form',
      'source' => 'google_form',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type
      ->set('source_configuration', [
        'source_field' => $source_field->getName(),
      ])
      ->save();

    $this->media = Media::create([
      'bundle' => 'google_form',
      'field_media_google_form' => 'http://google.com/forms/a/b/formid/viewform',
    ]);
    $this->media->save();
  }

  /**
   * Test methods on the google form source.
   */
  public function testGoogleFormSource() {
    $media_source = $this->media->getSource();
    $this->assertEquals('a/b/formid', $media_source->getMetadata($this->media, 'id'));

    $this->assertCount(1, $media_source->getMetadataAttributes());
    $this->assertArrayHasKey('id', $media_source->getMetadataAttributes());
    $this->assertArrayHasKey('google_forms', $media_source->getSourceFieldConstraints());
  }

}
