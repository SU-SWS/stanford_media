<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter;

/**
 * Class EmbeddableFormatterTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter
 */
class EmbeddableFormatterTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'image',
    'media',
    'path_alias',
    'stanford_media',
    'field',
    'file',
    'entity_test',
    'path_alias',
  ];

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $oembed_media;

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $unstructured_media;

  /**
   * A test embed string.
   *
   * @var string
   */
  protected $iframe_code = '<iframe src="http://www.test.com/"></iframe>';

  /**
   * Embeddable media type bundle.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  protected $mediaType;

  /**
   * @var \GuzzleHttp\Client
   */
  public $client;

  public $handler;

  public $handler_stack;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('path_alias');
    $this->installConfig('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

    $this->client = $this->createMock(Client::class);
    $this->client
      ->method('request')
      ->willReturnCallback([$this, 'getOembedCallback']);
    $this->client
      ->method('__call')
      ->willReturnCallback([$this, 'getMagicMethodCallback']);

    \Drupal::getContainer()->set('http_client', $this->client);

    $this->mediaType = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $this->mediaType->save();

    $this->mediaType->set('source_configuration', [
      'oembed_field_name' => 'field_media_embeddable_oembed',
      'unstructured_field_name' => 'field_media_embeddable_code',
      'thumbnails_directory' => 'public://oembed_thumbnails',
      'source_field' => 'field_media_embeddable_oembed',
    ])->save();

    // Create the fields we need.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_oembed',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'oembed',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_code',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'unstructured',
    ])->save();

    // Set up media instances.
    $this->oembed_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'oembed embeddable',
      'field_media_embeddable_oembed' => 'http://www.test.com/media-test',
    ]);
    $this->oembed_media->save();

    $this->unstructured_media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => $this->iframe_code,
    ]);
    $this->unstructured_media->save();

    $display_options = [
      'type' => 'embeddable_formatter',
    ];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'embeddable',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->setComponent('field_media_embeddable_code', $display_options)
      ->setComponent('field_media_embeddable_oembed', $display_options)
      ->removeComponent('thumbnail')
      ->save();
  }

  /**
   *
   */
  public function getOembedCallback($method, $url, $options) {
    switch ($url) {
      case 'http://www.test.com/media-test':
        $payload = [];
        break;

      case 'https://oembed.com/providers.json':
        $payload = [
          0 => [
            "provider_name" => "test",
            "provider_url" => "http://www.test.com",
            "endpoints" => [
              0 => [
                'schemes' => [
                  0 => "http://www.test.com/*",
                ],
                'url' => "http://www.test.com/oembed",
              ],
            ],
          ],
        ];
        break;

      case 'http://www.test.com/oembed?url=http://www.test.com/media-test':
      case 'http://www.test.com/oembed?url=http%3A//www.test.com/media-test':
        $payload = [
          'url' => 'https://twitter.com/_mcchris/status/1304093398182191104',
          'author_name' => 'mc chris',
          'author_url' => 'https://twitter.com/_mcchris',
          'html' => '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">how are nerds different now than they were 20 years ago?</p>&mdash; mc chris</blockquote>
            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
            ',
          'width' => 550,
          'height' => NULL,
          'type' => 'rich',
          'cache_age' => '3153600000',
          'provider_name' => 'test',
          'provider_url' => 'http://www.test.com',
          'version' => '1.0',
        ];
        break;

      default:
        $payload = ['error' => 'something went wrong.'];
    }
    return new Response(200, ['Content-type' => 'application/json'], json_encode($payload));
  }

  /**
   * Callback for guzzle _call magic method.
   */
  public function getMagicMethodCallback($method, $args) {
    return $this->getOembedCallback($method, $args[0], []);
  }

  /**
   * Formatter should not apply to things that aren't media.
   */
  public function testNonMediaField() {
    EntityTestBundle::create(['id' => 'test'])->save();

    $field_storage = FieldStorageConfig::create([
      'type' => 'entity_reference',
      'field_name' => 'field_test_media',
      'entity_type' => 'entity_test',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
    ]);
    $field_config->save();

    $this->assertFalse(EmbeddableFormatter::isApplicable($field_config));
  }

  /**
   * Formatter should not apply to things that aren't embeddable.
   */
  public function testOtherMediaTypeField() {
    $mediaType = MediaType::create([
      'id' => 'video',
      'label' => 'video',
      'source' => 'oembed:video',
    ]);
    $mediaType->save();
    $source_field = $mediaType->getSource()->createSourceField($mediaType);

    $this->assertFalse(EmbeddableFormatter::isApplicable($source_field));
  }

  /**
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::isApplicable
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewElements
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewUnstructuredElements
   * @covers Drupal\stanford_media\Plugin\Field\FieldFormatter\EmbeddableFormatter::viewOEmbedElements
   */
  public function testEmbeddableFormatter() {
    $source_field = $this->oembed_media->getSource()
      ->getSourceFieldDefinition($this->mediaType);
    $this->assertTrue(EmbeddableFormatter::isApplicable($source_field));

    $source_field = $this->unstructured_media->getSource()
      ->getSourceFieldDefinition($this->mediaType);
    $this->assertTrue(EmbeddableFormatter::isApplicable($source_field));

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('media');
    $view_render = $view_builder->view($this->unstructured_media, 'default');
    $rendered_view = \Drupal::service('renderer')->renderInIsolation($view_render);
    $this->assertStringContainsString('http://www.test.com', $rendered_view);

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('media');

    $view_render = $view_builder->view($this->oembed_media, 'default');
    $rendered_view = \Drupal::service('renderer')->renderRoot($view_render);

    $this->assertStringContainsString('iframe', $rendered_view);
    $this->assertStringContainsString('www.test.com/media-test', $rendered_view);
    $this->assertStringContainsString('media-oembed-content', $rendered_view);
    $this->assertStringNotContainsString('frameborder', $rendered_view);
    $this->assertStringNotContainsString('scrolling', $rendered_view);
    $this->assertStringNotContainsString('allowtransparency', $rendered_view);
  }

  /**
   * Display settings on the formatter test.
   */
  public function testDisplayFormSettings() {
    $source_field = $this->unstructured_media->getSource()
      ->getSourceFieldDefinition($this->mediaType);
    $configuration = [
      'field_definition' => $source_field,
      'settings' => [],
      'label' => 'foo',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager */
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');
    /** @var EmbeddableFormatter $formatter */
    $formatter = $formatter_plugin_manager->createInstance('embeddable_formatter', $configuration);

    $element = ['#parents' => ['allowed_tags']];
    $form_state = new FormState();
    $form_state->setValue('allowed_tags', '<foo> <bar>     div 123 &$%');
    $form = [];
    $this->assertNotEmpty($formatter->settingsForm($form, $form_state));
    $formatter->validateAllowedTags($element, $form_state, $form);
    $this->assertEquals('foo bar div', $form_state->getValue('allowed_tags'));
  }

}
