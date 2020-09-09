<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\media\Source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;

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
    'field_permissions',
    'stanford_media',
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $oembed_media;
  protected $unstructured_media;

  protected $flickr_link = 'https://www.flickr.com/photos/30077443@N07/4386329831/in/photolist-7FB63p-ekGKzG-bwP3zS-8xJdWU-dM6rTZ-9Gi9gW-4xEEHm-fJ28kk-7m85rb-dM8NH2-8rqaQP-5qRfka-68viGv-DLPC-599YKW-9peEKV-6Fikka-etmHxc-dM6nXn-nHtgA-5NJzeK-5nnG9M-jSkSSz-544VeB-5sPBSA-96urM1-4CtJWi-xbjLoW-8QWX46-5JUVLe-a4GGY8-4xA7ze-7f1BRX-8QX4eR-jrHVUR-5YxxLB-78DJKF-fP1kjU-CL3tb-qNYhGk-5ah2ZT-5ahhMB-4rWvTw-5sydqH-i3jjqs-49G1xB-923XE9-fNHPbi-5qsYS9-ekjoT';
  protected $iframe_code = '<iframe src="http://www.test.com"></iframe>';

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
    //$this->installConfig('stanford_media');


    $media_type = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $media_type->save();

    $media_type
      ->set('source_configuration', [
        'oembed_field_name' => 'field_media_embeddable_oembed',
        'unstructured_field_name' => 'field_media_embeddable_code',
        'thumbnails_directory' => 'public://oembed_thumbnails',
        'source_field' => 'field_media_embeddable_oembed',
      ])
      ->save();


    $this->oembed_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'oembed embeddable',
      'field_media_embeddable_code' => '',
      'field_media_embeddable_oembed' => $this->flickr_link,
    ]);
    $this->oembed_media->save();

    $this->unstructured_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => $this->iframe_code,
      'field_media_embeddable_oembed' => '',
    ]);
    $this->unstructured_media->save();

  }

  /**
   * Test methods on the embeddable source.
   */
  public function testEmbeddableSource() {
    /*
    $media_source = $this->media->getSource();
    $this->assertEquals('a/b/formid', $media_source->getMetadata($this->media, 'id'));

    $this->assertCount(1, $media_source->getMetadataAttributes());
    $this->assertArrayHasKey('id', $media_source->getMetadataAttributes());
    $this->assertArrayHasKey('embeddables', $media_source->getSourceFieldConstraints());
    */
  }

}
