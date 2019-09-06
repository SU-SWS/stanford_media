<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Field\FieldFormatter;

use Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter;

/**
 * Class MediaFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter
 */
class MediaImageFormatterTest extends FieldFormatterTestBase {

  /**
   * Field formatter plugin.
   *
   * @var \Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = [
      'field_definition' => $this->fieldDefinition,
      'settings' => ['image_style' => 'foo'],
      'label' => '',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ];

    $this->plugin = TestMediaImageFormatter::create($this->container, $config, '', []);
  }

  /**
   * Static method returns.
   */
  public function testStaticMethods() {
    $this->assertArrayEquals([
      'image_style' => NULL,
      'link' => 0,
      'view_mode' => 'default',
    ], $this->plugin::defaultSettings());

    $this->assertTrue($this->plugin::isApplicable($this->fieldDefinition));
    $this->fieldTargetIsMedia = FALSE;
    $this->assertFALSE($this->plugin::isApplicable($this->fieldDefinition));
  }

  /**
   * PreRender sets the image style and links.
   */
  public function testPreRender() {
    $element = [
      '#media' => $this->getMockMediaEntity(),
      '#stanford_media_image_style' => 'style_foo',
      'field_foo' => [
        0 => [],
      ],
    ];
    $element = $this->plugin->preRender($element);
    $this->assertEquals('image', $element['field_foo']['#formatter']);
    $this->assertEquals('image_formatter', $element['field_foo'][0]['#theme']);
    $this->assertEquals('style_foo', $element['field_foo'][0]['#image_style']);

    $this->assertArrayNotHasKey('#url', $element['field_foo'][0]);

    $element['#stanford_media_url'] = 'http://foo.bar';
    $element['#stanford_media_url_title'] = 'Foo Bar';
    $element = $this->plugin->preRender($element);
    $this->assertArrayHasKey('#url', $element['field_foo'][0]);
    $this->assertEquals('http://foo.bar', $element['field_foo'][0]['#url']);
    $this->assertEquals('Foo Bar', $element['field_foo'][0]['#attributes']['title']);
  }

}

/**
 * Class TestMediaImageFormatter to return style options.
 */
class TestMediaImageFormatter extends MediaImageFormatter {

  /**
   * {@inheritDoc}}
   */
  protected function getStyleOptions() {
    return [
      'foo' => 'Foo',
      'bar' => 'Bar',
    ];
  }

}
