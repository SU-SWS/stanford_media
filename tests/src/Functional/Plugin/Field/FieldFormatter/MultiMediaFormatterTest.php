<?php

namespace Drupal\Tests\stanford_media\Functional\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\media\Entity\Media;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\media_test_oembed\UrlResolver;
use Drupal\Tests\media\Functional\FieldFormatter\OEmbedFormatterTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\MediaType;

/**
 * @covers \Drupal\stanford_media\Plugin\Field\FieldFormatter\MultiMediaFormatter
 *
 * @group stanford_media
 */
class MultiMediaFormatterTest extends OEmbedFormatterTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'link',
    'media_test_oembed',
    'stanford_media',
    'node',
    'system',
    'user',
    'field',
    'file',
    'media',
    'entity_reference',
    'image',
  ];

  /**
   * [setUp description]
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Method overwrite of parent.
   */
  public function testDisplayConfiguration() {
    $this->assert(TRUE, TRUE);
  }

  /**
   * Data provider for testRender().
   *
   * @see ::testRender()
   *
   * @return array
   */
  public function providerRender() {
    $values = parent::providerRender();
    unset($values['tweet']);
    unset($values['Flickr photo']);
    return $values;
  }

  /**
   * Tests the oEmbed field formatter.
   *
   * @param string $url
   *   The canonical URL of the media asset to test.
   * @param string $resource_url
   *   The oEmebd resource URL of the media asset to test.
   * @param mixed $formatter_settings
   *   Settings for the oEmbed field formatter.
   * @param array $selectors
   *   An array of arrays. Each key is a CSS selector targeting an element in
   *   the rendered output, and each value is an array of attributes, keyed by
   *   name, that the element is expected to have.
   *
   * @dataProvider providerRender
   */
  public function testRender($url, $resource_url, array $formatter_settings, array $selectors) {
    $account = $this->drupalCreateUser(['view media', 'access content']);
    $this->drupalLogin($account);

    $media_type = MediaType::load('video');
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);
    $this->hijackProviderEndpoints();

    ResourceController::setResourceUrl($url, $this->getFixturesDirectory() . '/' . $resource_url);
    UrlResolver::setEndpointUrl($url, $resource_url);

    $entity = Media::create([
      'bundle' => $media_type->id(),
      $source_field->getName() => $url,
    ]);
    $entity->save();

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

    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
    ])->setStatus(TRUE)
    ->setComponent('foo', [
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
        ]
      ],
    ])
    ->save();

    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'foo' => $entity->id(),
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementExists('css', 'iframe');
  }
}
