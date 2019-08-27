<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\media\Source;

use Drupal\Core\Field\FieldItemList;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Plugin\media\Source\AudioEmbedField;

/**
 * Class AudioEmbedFieldTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\media\Source\AudioEmbedField
 */
class AudioEmbedFieldTest extends MediaSourcePluginTestBase {

  /**
   * @var bool
   */
  protected $returnFieldDefinition = FALSE;

  protected $returnFieldValue = FALSE;

  /**
   * @var \Drupal\stanford_media\Plugin\media\Source\AudioEmbedField
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = AudioEmbedField::create($this->container, ['source_field' => 'foo'], '', ['label' => 'bar']);
  }

  /**
   * The plugin gets created with correct methods.
   */
  public function testPluginCreation() {
    $this->assertArrayEquals(['source_field' => 'field_media_audio_embed_field'], $this->plugin->defaultConfiguration());
    $this->assertArrayEquals(['source_field' => 'foo'], $this->plugin->getConfiguration());
    $attributes = [
      'id',
      'source',
      'source_name',
      'image_local',
      'image_local_uri',
    ];
    $this->assertCount(count($attributes), $this->plugin->getMetadataAttributes());
    foreach ($attributes as $key) {
      $this->arrayHasKey($key, $this->plugin->getMetadataAttributes());
    }

    $media_type = $this->createMock(MediaTypeInterface::class);

    $this->assertNull($this->plugin->getSourceFieldDefinition($media_type));

    $this->returnFieldDefinition = TRUE;
    $this->assertNotEmpty($this->plugin->getSourceFieldDefinition($media_type));
  }

  /**
   * A field is created.
   */
  public function testCreateField() {
    $media_type = $this->createMock(MediaTypeInterface::class);
    $this->assertNotEmpty($this->plugin->createSourceField($media_type));
  }

  /**
   * If the entity doesn't have a audio url, metadata is limited.
   */
  public function testMetadataNoUrl() {
    $entity = $this->getMediaEntity();
    $this->assertEquals('media:audio:foo-bar-baz', $this->plugin->getMetadata($entity, 'default_name'));
    $this->assertNull($this->plugin->getMetadata($entity, $this->randomMachineName()));
  }

  /**
   * Audio metadata methods.
   */
  public function testMetaDataWithUrl() {
    $this->returnFieldValue = TRUE;
    $entity = $this->getMediaEntity();
    $this->assertNull($this->plugin->getMetadata($entity, $this->randomMachineName()));

    $this->assertEquals('foo bar baz', $this->plugin->getMetadata($entity, 'default_name'));
    $this->assertEquals('foo-baz', $this->plugin->getMetadata($entity, 'id'));
    $this->assertEquals('foo_bar', $this->plugin->getMetadata($entity, 'source'));
    $this->assertEquals('foo_bar', $this->plugin->getMetadata($entity, 'source_name'));
    $this->assertEquals(__DIR__ . '/MediaSourcePluginTestBase.php', $this->plugin->getMetadata($entity, 'thumbnail_uri'));
    $this->assertEquals(__DIR__ . '/MediaSourcePluginTestBase.php', $this->plugin->getMetadata($entity, 'image_local'));
    $this->assertEquals(__DIR__ . '/MediaSourcePluginTestBase.php', $this->plugin->getMetadata($entity, 'image_local_uri'));

  }

  /**
   * Get a mock media entity.
   */
  protected function getMediaEntity() {
    $media_source = $this->createMock(MediaSourceInterface::class);
    $media_source->method('getConfiguration')
      ->willReturn(['source_field' => 'foo']);

    $field_list = $this->createMock(FieldItemList::class);
    $field_list->method('getString')
      ->willReturn($this->returnFieldValue ? 'foo' : NULL);

    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($media_source);
    $entity->method('get')->willReturn($field_list);
    $entity->method('bundle')->willReturn('audio');
    $entity->method('uuid')->willReturn('foo-bar-baz');

    return $entity;
  }

  /**
   * Mock Entity Field Manager method callback.
   */
  public function getFieldDefinitionsCallback() {
    if ($this->returnFieldDefinition) {
      return ['foo' => TRUE];
    }
  }

}
