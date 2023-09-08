<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Field\FieldFormatter;

use Drupal\stanford_media\Plugin\Field\FieldFormatter\MultiMediaFormatter;

/**
 * Class MultiMediaFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter
 */
class MultiMediaFormatterTest extends FieldFormatterTestBase {

  /**
   * Field formatter plugin.
   *
   * @var \Drupal\stanford_media\Plugin\Field\FieldFormatter\MultiMediaFormatter
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    $this->container->set('string_translation', $this->getStringTranslationStub());

    $config = [
      'field_definition' => $this->fieldDefinition,
      'settings' => ['image_style' => 'foo'],
      'label' => '',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ];

    $this->plugin = MultiMediaFormatter::create($this->container, $config, '', []);
    \Drupal::setContainer($this->container);
  }

  /**
   * Testing settings summary.
   */
  public function testSettingsSummary() {
    $summary = $this->plugin->settingsSummary();
    $this->assertTrue(is_array($summary));
    $this->assertTrue(count($summary) == 1);
  }

  /**
   * Static method returns.
   */
  public function testDefaultSettings() {
    $this->assertEquals([
      'link' => FALSE,
      'view_mode' => 'default',
      'image' => [
        'image_formatter' => 'image_style',
        'image_formatter_image_style' => 'large',
        'image_formatter_responsive_image_style' => 'full_responsive',
        'image_formatter_view_mode' => 'default',
      ],
      'video' => [
        'video_formatter' => 'entity',
        'video_formatter_view_mode' => 'default',
      ],
      'other' => [
        'view_mode' => 'default',
      ],
    ], $this->plugin::defaultSettings());
  }

}
