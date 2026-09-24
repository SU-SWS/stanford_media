<?php

namespace Drupal\Tests\stanford_media\Unit\Hook;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\stanford_media\Hook\StanfordMediaThemeHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the theme and preprocess hooks.
 */
#[Group('stanford_media')]
class StanfordMediaThemeHooksTest extends UnitTestCase {

  /**
   * Hook class being tested.
   *
   * @var \Drupal\stanford_media\Hook\StanfordMediaThemeHooks
   */
  protected $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $media_type = $this->createMock(MediaTypeInterface::class);
    $media_type_storage = $this->createMock(EntityStorageInterface::class);
    $media_type_storage->method('load')->willReturn($media_type);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('media_type')
      ->willReturn($media_type_storage);

    $url_resolver = $this->createMock(UrlResolverInterface::class);
    $url_resolver->method('getResourceUrl')
      ->willReturnCallback(fn($url) => 'https://oembed.test?url=' . $url);

    $module_list = $this->createMock(ModuleExtensionList::class);
    $module_list->method('getPath')->with('stanford_media')
      ->willReturn('modules/custom/stanford_media');

    $this->hooks = new StanfordMediaThemeHooks($entity_type_manager, $url_resolver, $module_list);
  }

  /**
   * Test the oembed resource url is added to video fields.
   */
  public function testPreprocessField(): void {
    // Non media fields are not changed.
    $variables = ['entity_type' => 'node'];
    $this->hooks->preprocessField($variables);
    $this->assertEquals(['entity_type' => 'node'], $variables);

    // Missing field items are not changed.
    $variables = ['entity_type' => 'media', 'element' => ['#items' => NULL]];
    $this->hooks->preprocessField($variables);
    $this->assertArrayNotHasKey('#attached', $variables);

    // Only the source field of oembed videos are changed.
    $variables = $this->getFieldVariables('image', 'field_media_oembed_video');
    $this->hooks->preprocessField($variables);
    $this->assertArrayNotHasKey('#attached', $variables);

    $variables = $this->getFieldVariables('oembed:video', 'field_foo');
    $this->hooks->preprocessField($variables);
    $this->assertArrayNotHasKey('#attached', $variables);

    $variables = $this->getFieldVariables('oembed:video', 'field_media_oembed_video');
    $this->hooks->preprocessField($variables);
    $resource = 'https://oembed.test?url=https://www.youtube.com/watch?v=foo';

    // Items with a title already are skipped.
    $this->assertArrayNotHasKey('data-oembed-resource', $variables['items'][0]['content']['#attributes']);
    $this->assertEquals($resource, $variables['items'][1]['content']['#attributes']['data-oembed-resource']);
    $this->assertEquals($resource, $variables['items'][1]['content']['#iframe']['#attributes']['data-oembed-resource']);
    // Invalid urls are skipped.
    $this->assertArrayNotHasKey('#attributes', $variables['items'][2]['content']);
    $this->assertEquals(['stanford_media/display'], $variables['#attached']['library']);
  }

  /**
   * Test the dropzone template path is changed.
   */
  public function testThemeRegistryAlter(): void {
    $registry = ['dropzonejs' => ['path' => 'foo']];
    $this->hooks->themeRegistryAlter($registry);
    $this->assertEquals('modules/custom/stanford_media/templates', $registry['dropzonejs']['path']);
  }

  /**
   * Test the allowed files variable for dropzone.
   */
  public function testPreprocessDropzonejs(): void {
    $variables = ['element' => ['#extensions' => 'jpg png gif']];
    $this->hooks->preprocessDropzonejs($variables);
    $this->assertEquals('jpg, png, gif', $variables['allowed_files']);
  }

  /**
   * Test file media get a description from the media name.
   */
  public function testPreprocessMedia(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('field_media_file');

    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getPluginId')->willReturnOnConsecutiveCalls('image', 'file', 'file');
    $source->method('getSourceFieldDefinition')->willReturn($field_definition);
    $media = $this->createMock(MediaInterface::class);
    $media->method('getSource')->willReturn($source);
    $media->method('bundle')->willReturn('file');

    // Non file media is unchanged.
    $variables = ['media' => $media, 'name' => 'Foo', 'content' => []];
    $this->hooks->preprocessMedia($variables);
    $this->assertEmpty($variables['content']);

    $variables = ['media' => $media, 'name' => 'Foo', 'content' => []];
    $this->hooks->preprocessMedia($variables);
    $this->assertEquals('Foo', $variables['content']['field_media_file'][0]['#description']);

    // An existing description is kept.
    $variables['content']['field_media_file'][0]['#description'] = 'Bar';
    $this->hooks->preprocessMedia($variables);
    $this->assertEquals('Bar', $variables['content']['field_media_file'][0]['#description']);
  }

  /**
   * Test the caption class is added.
   */
  public function testPreprocessFilterCaption(): void {
    $variables = [];
    $this->hooks->preprocessFilterCaption($variables);
    $this->assertEquals('caption', $variables['classes']);

    $variables = ['classes' => 'foo'];
    $this->hooks->preprocessFilterCaption($variables);
    $this->assertEquals('foo caption', $variables['classes']);
  }

  /**
   * Get the preprocess variables for a media field.
   *
   * @param string $source_id
   *   Media source plugin id.
   * @param string $source_field
   *   Media source field name.
   *
   * @return array
   *   Preprocess variables.
   */
  protected function getFieldVariables(string $source_id, string $source_field): array {
    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getPluginId')->willReturn($source_id);
    $source->method('getConfiguration')
      ->willReturn(['source_field' => $source_field]);

    $urls = [
      'https://www.youtube.com/watch?v=foo',
      'https://www.youtube.com/watch?v=foo',
      'not a url',
    ];
    $field_values = $this->createMock(FieldItemListInterface::class);
    $field_values->method('get')->willReturnCallback(function ($delta) use ($urls) {
      $item = $this->createMock(FieldItemInterface::class);
      $item->method('getString')->willReturn($urls[$delta]);
      return $item;
    });

    $media = $this->createMock(MediaInterface::class);
    $media->method('getSource')->willReturn($source);
    $media->method('get')->willReturn($field_values);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($media);
    $items->method('getName')->willReturn('field_media_oembed_video');

    return [
      'entity_type' => 'media',
      'field_name' => 'field_media_oembed_video',
      'element' => ['#items' => $items],
      'items' => [
        ['content' => ['#attributes' => ['title' => 'Foo']]],
        ['content' => ['#iframe' => []]],
        ['content' => []],
      ],
    ];
  }

}
