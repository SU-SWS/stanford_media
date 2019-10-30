<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Field\FieldFormatter;

use Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaResponsiveImageFormatter;

/**
 * Class MediaResponsiveImageFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaResponsiveImageFormatter
 */
class MediaResponsiveImageFormatterTest extends FieldFormatterTestBase {

  /**
   * Field formatter plugin.
   *
   * @var \Drupal\Tests\stanford_media\Unit\Plugin\Field\FieldFormatter\TestMediaResponsiveImageFormatter
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

    $this->plugin = TestMediaResponsiveImageFormatter::create($this->container, $config, '', []);
  }

  /**
   * Style options array.
   */
  public function testStyleOptions() {
    $options = $this->plugin->getStyleOptions();
    $this->assertEquals(['foo' => 'Foo'], $options);
  }

  /**
   * PreRender sets the responsive image styles.
   */
  public function testPreRender() {
    $element = [
      '#media' => $this->getMockMediaEntity(),
      '#stanford_media_image_style' => 'foo',
      'field_foo' => [
        0 => [],
      ],
    ];
    $element = $this->plugin->preRender($element);
    $this->assertEquals('responsive_image', $element['field_foo']['#formatter']);
    $this->assertEquals('responsive_image_formatter', $element['field_foo'][0]['#theme']);
    $this->assertEquals('foo', $element['field_foo'][0]['#responsive_image_style_id']);
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
 * Class TestMediaResponsiveImageFormatter to make methods public.
 */
class TestMediaResponsiveImageFormatter extends MediaResponsiveImageFormatter {

  /**
   * {@inheritDoc}
   */
  public function getStyleOptions() {
    return parent::getStyleOptions();
  }

}
