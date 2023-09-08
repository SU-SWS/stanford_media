<?php
/**
 * @file
 */

namespace Drupal\Tests\stanford_media\Kernel\Plugin\Filter;

use Drupal\Tests\media\Kernel\MediaEmbedFilterTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * MediaEmbedMarkupFilter Tests.
 *
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Filter\MediaEmbedMarkupFilter
 * @group stanford_media
 */
class MediaEmbedMarkupFilterTest extends MediaEmbedFilterTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'system',
    'text',
    'user',
    'breakpoint',
    'responsive_image',
    'responsive_image_test_module',
    'stanford_media',
  ];

  /**
   * Test the filter.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterMarkup() {
    $embed_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
      'data-view-mode' => 'dupededupe',
    ];

    // Create Responsive Image Style.
    ResponsiveImageStyle::create([
      'id' => 'full',
      'label' => 'full',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'large',
      ])
      ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => '',
      ])
      ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '(min-width: 700px) 700px, 100vw',
          'sizes_image_styles' => [],
        ],
      ])
      ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => '',
      ])
      ->save();

    EntityViewMode::create([
      'id' => 'media.dupededupe',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => $this->randomMachineName(),
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image',
      'mode' => 'dupededupe',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->removeComponent('created')
      ->removeComponent('uid')
      ->setComponent('field_media_image', [
        'label' => 'visually_hidden',
        'type' => 'responsive_image',
        'settings' => [
          'responsive_image_style' => 'full',
          'image_link' => '',
        ],
        'third_party_settings' => [],
        'weight' => 1,
        'region' => 'content',
      ])
      ->save();

    $content = $this->createEmbedCode($embed_attributes);
    $filter_result = $this->processText($content, "en", ['media_embed', 'stanford_media_embed_markup']);
    $output = $filter_result->getProcessedText();

    $this->assertStringNotContainsString("</source>", $output);
  }

}
