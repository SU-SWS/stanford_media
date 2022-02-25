<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Class MediaFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter
 */
class MediaImageFormatterTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'image',
    'stanford_media',
    'node',
    'system',
    'user',
    'field',
    'file',
    'media',
    'entity_reference',
  ];

  /**
   * Created media entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $mediaEntity;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('media');

    // Set up media bundle and fields.
    $media_type = MediaType::create([
      'label' => 'Image',
      'id' => 'image',
      'description' => 'Image type.',
      'source' => 'image',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();

    $media_display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image',
      'mode' => 'default',
      'content' => [],
    ])->setStatus(TRUE);
    $media_display->setComponent($source_field->getName());
    $media_display->removeComponent('thumbnail');
    $media_display->save();

    ImageStyle::create(['name' => 'large', 'label' => 'Large'])->save();
    NodeType::create(['type' => 'article'])
      ->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'foo',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'media'],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Foo',
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
    ])->setStatus(TRUE);

    $display->setComponent('foo', [
      'type' => 'media_image_formatter',
      'settings' => [
        'view_mode' => 'default',
        'image_style' => 'large',
        'link' => TRUE,
        'remove_alt' => FALSE,
      ],
    ]);
    $display->save();

    \Drupal::service('file_system')
      ->copy(__DIR__ . '/logo.png', 'public://logo.png', TRUE);
    $file = File::create(['uri' => 'public://logo.png']);
    $file->save();
    $this->mediaEntity = Media::create([
      'bundle' => 'image',
      $source_field->getName() => ['target_id' => $file->id(), 'alt' => 'Foo Bar Alt'],
    ]);
    $this->mediaEntity->save();

    DateFormat::create([
      'id' => 'fallback',
      'pattern' => 'D, m/d/Y - H:i',
    ])->save();

    // Create anonymous user role.
    Role::create([
      'id' => 'anonymous',
      'label' => 'anonymous',
    ])->save();
    user_role_grant_permissions('anonymous', ['view media']);
  }

  /**
   * Test the image style formatter is used correctly.
   */
  public function testFieldFormatter() {
    $this->assertNotEmpty($this->mediaEntity->id());

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $node */
    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'foo' => $this->mediaEntity->id(),
    ]);
    $node->save();

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('node');
    $node_render = $view_builder->view($node, 'default');
    $rendered_node = \Drupal::service('renderer')->renderPlain($node_render);
    preg_match_all('/<a.*href="\/node\/.*\/large\/.*\/logo.png.*\/a>/s', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);
    preg_match_all('/alt="Foo Bar Alt"/', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);

    EntityViewDisplay::load('node.article.default')->setComponent('foo', [
      'type' => 'media_image_formatter',
      'settings' => [
        'view_mode' => 'default',
        'image_style' => 'large',
        'link' => TRUE,
        'remove_alt' => TRUE,
      ],
    ])->save();

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('node');
    $node_render = $view_builder->view($node, 'default');
    $rendered_node = \Drupal::service('renderer')->renderPlain($node_render);
    preg_match_all('/<a.*href="\/node\/.*\/large\/.*\/logo.png.*\/a>/s', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);
    preg_match_all('/alt="Foo Bar Alt"/', $rendered_node, $preg_match);
    $this->assertEmpty($preg_match[0]);
  }

}
