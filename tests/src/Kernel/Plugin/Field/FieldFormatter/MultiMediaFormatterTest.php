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
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Class MultiMediaFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\MultiMediaFormatter
 */
class MultiMediaFormatterTest extends KernelTestBase {

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
    'breakpoint',
    'responsive_image',
  ];

  /**
   * Created Media Entities.
   *
   * @var array
   */
  protected $mediaEntities = [];

  /**
   * Media types to test.
   *
   * @var array
   */
  protected $mediaTypes = ['image', 'file'];

  /**
   * Source fields.
   *
   * @var array
   */
  protected $sourceFields = [];

  /**
   * Display of the node.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $entityDisplay;

  /**
   * File entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('media');
    $this->installConfig('media');

    // Ceate a Date Format.
    DateFormat::create([
      'id' => 'fallback',
      'pattern' => 'D, m/d/Y - H:i',
    ])->save();

    // Create anonymous user role.
    Role::create([
      'id' => 'anonymous',
      'label' => 'anonymous',
    ])->save();

    // Gotta see something.
    user_role_grant_permissions('anonymous', ['view media']);

    // Create Image Styles.
    ImageStyle::create(['name' => 'large', 'label' => 'Large'])->save();

    // Create Responsive Image Style.
    ResponsiveImageStyle::create([
      'id' => 'full',
      'label' => 'Full',
      'breakpoint_group' => 'foo',
    ])->save();

    // Create a node type.
    NodeType::create(['type' => 'article'])->save();

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

    $this->entityDisplay = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
    ])->setStatus(TRUE);

    // Create Media Entity Types.
    $this->createMediaTypes();
    $this->createSourceFields();
    $this->createEntityViewDisplays();

    // Create a file.
    \Drupal::service('file_system')
      ->copy(__DIR__ . '/logo.png', 'public://logo.png', TRUE);
    $this->file = File::create(['uri' => 'public://logo.png']);
    $this->file->save();

    // Create the media entities.
    $this->createMediaEntities();
  }

  /**
   * Create the media entities.
   */
  private function createMediaEntities() {
    // Create the image media entity.
    $this->mediaEntities['image'] = Media::create([
      'bundle' => 'image',
      $this->sourceFields['image']->getName() => $this->file->id(),
    ]);
    $this->mediaEntities['image']->save();

    // Create the file media entity.
    $this->mediaEntities['file'] = Media::create([
      'bundle' => 'file',
      $this->sourceFields['file']->getName() => $this->file->id(),
    ]);
    $this->mediaEntities['file']->save();
  }

  /**
   * Create the types.
   */
  private function createMediaTypes() {
    // Image.
    $this->mediaEntities['image'] = MediaType::create([
      'label' => 'Image',
      'id' => 'image',
      'description' => 'Image type.',
      'source' => 'image',
    ]);
    $this->mediaEntities['image']->save();

    // Other (file)
    $this->mediaEntities['file'] = MediaType::create([
      'label' => 'File',
      'id' => 'file',
      'description' => 'File type.',
      'source' => 'file',
    ]);
    $this->mediaEntities['file']->save();
  }

  /**
   * Create the source fields.
   */
  private function createSourceFields() {
    foreach ($this->mediaEntities as $type => $media_type) {
      $source_field = $media_type->getSource()->createSourceField($media_type);
      $source_field->getFieldStorageDefinition()->save();
      $source_field->save();

      $media_type->set('source_configuration', [
        'source_field' => $source_field->getName(),
      ])->save();

      $this->sourceFields[$type] = $source_field;
    }
  }

  /**
   * Create the displays.
   */
  private function createEntityViewDisplays() {
    foreach ($this->mediaEntities as $type => $media_type) {
      $media_display = EntityViewDisplay::create([
        'targetEntityType' => 'media',
        'bundle' => $type,
        'mode' => 'default',
        'content' => [],
      ])->setStatus(TRUE);
      $media_display->setComponent($this->sourceFields[$type]->getName());
      $media_display->removeComponent('thumbnail');
      $media_display->save();
    }
  }

  /**
   * Get the rendered output of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   * @param string $view_mode
   *
   * @return string
   */
  private function getRenderedNode($node, $view_mode = 'default') {
    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('node');
    $node_render = $view_builder->view($node, $view_mode);
    return \Drupal::service('renderer')->renderPlain($node_render);
  }

  /**
   * Get a node object with a reference to a type.
   *
   * @param string $entity
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getANode($entity = "image") {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $node */
    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'foo' => $this->mediaEntities[$entity]->id(),
    ]);
    $node->save();
    return $node;
  }

  /**
   * Get the formatter settings.
   *
   * @return array
   */
  private function getFormatterSettings() {
    return [
      'type' => 'media_multimedia_formatter',
      'settings' => [
        'view_mode' => 'default',
        'link' => FALSE,
        'image_style' => 'large',
        'image' => [
          'image_formatter' => 'image_style',
          'image_formatter_image_style' => 'large',
          'image_formatter_responsive_image_style' => 'full',
          'image_formatter_view_mode' => 'default',
        ],
        'video' => [
          'video_formatter' => 'entity',
          'video_formatter_view_mode' => 'default',
        ],
        'other' => [
          'view_mode' => 'default',
        ],
      ],
    ];
  }

  /**
   * Test the image style formatter is used correctly.
   */
  public function testImageFormatter() {
    $formatter = $this->getFormatterSettings();
    $this->entityDisplay->setComponent('foo', $formatter);
    $this->entityDisplay->save();

    $node = $this->getANode();
    $rendered_node = $this->getRenderedNode($node);
    preg_match_all('/<img.*src=".*\/large\/.*\/logo.png.*\/>/s', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);
  }

  /**
   * Test the responsive image style formatter is used correctly.
   */
  public function testResponsiveImageFormatter() {
    $formatter = $this->getFormatterSettings();
    $formatter['settings']['image']['image_formatter'] = "responsive_image_style";
    $this->entityDisplay->setComponent('foo', $formatter);
    $this->entityDisplay->save();

    $node = $this->getANode();
    $rendered_node = $this->getRenderedNode($node);
    preg_match_all('/<picture.*\/>/s', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);
  }

  /**
   * Test the image entity view mode is used correctly.
   */
  public function testImageFormatterViewMode() {
    $formatter = $this->getFormatterSettings();
    $formatter['settings']['image']['image_formatter'] = "entity";
    $this->entityDisplay->setComponent('foo', $formatter);
    $this->entityDisplay->save();

    $node = $this->getANode();
    $rendered_node = $this->getRenderedNode($node);
    preg_match_all('/<img.*src=".*\/logo.png.*\/>/s', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);

    // Now delete the media and make sure it doesn't break.
    $this->mediaEntities['image']->delete();
    $node = Node::load($node->id());
    $rendered_node = $this->getRenderedNode($node);

    preg_match_all('/<img.*src=".*\/logo.png.*\/>/s', $rendered_node, $preg_match);
    $this->assertEmpty($preg_match[0]);
  }

  /**
   * Test the File Entity formatter is used correctly.
   */
  public function testFileFormatterViewMode() {
    $formatter = $this->getFormatterSettings();
    $this->entityDisplay->setComponent('foo', $formatter);
    $this->entityDisplay->save();
    $node = $this->getANode("file");
    $rendered_node = $this->getRenderedNode($node);
    preg_match_all('/file--mime-image-png/', $rendered_node, $preg_match);
    $this->assertNotEmpty($preg_match[0]);
  }

}
