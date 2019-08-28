<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\Image;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Class Image.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\Image
 */
class ImageTest extends MediaEmbedDialogTestBase {

  protected $loadMultipleThrowError = FALSE;

  /**
   * Plugin to test on.
   *
   * @var \Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog\TestImage
   */
  protected $plugin;

  protected $linkitEnabled = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mediaBundle = 'image';
    $this->plugin = TestImage::create($this->container, ['entity' => $this->mediaEntity], '', []);
  }

  public function testApplicable() {
    $plugin = Image::create($this->container, ['entity' => new \stdClass()], '', []);
    $this->assertFalse($plugin->isApplicable());

    $plugin = Image::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $this->assertTrue($plugin->isApplicable());

    $this->mediaBundle = 'file';
    $this->assertFalse($plugin->isApplicable());

    $this->mediaBundle = 'image';
    $this->assertArrayEquals([
      'image_style' => NULL,
      'alt_text' => NULL,
      'title_text' => NULL,
      'linkit' => [],
    ], $plugin->getDefaultInput());
  }

  public function testUrlLink() {
    $this->assertInstanceOf(Url::class, $this->plugin::getLinkObject('/foo/bar'));
    $this->assertInstanceOf(Url::class, $this->plugin::getLinkObject('internal:/foo/bar'));
  }

  public function testCaptionDefault() {
    $form = ['attributes' => ['data-caption' => []]];
    $form_state = new FormState();
    $this->assertArrayEquals([], $this->plugin->getCaptionDefault($form, $form_state));

    $form_state->setUserInput(['attributes' => [MediaEmbedDialogInterface::SETTINGS_KEY => ['caption' => ['foo' => 'bar']]]]);
    $this->assertArrayEquals(['foo' => 'bar'], $this->plugin->getCaptionDefault($form, $form_state));

    $form['attributes']['data-caption']['#default_value'] = json_encode(['bar' => 'foo']);
    $this->assertArrayEquals(['bar' => 'foo'], $this->plugin->getCaptionDefault($form, $form_state));
  }

  public function testAlterDialog() {
    $form = ['attributes' => ['data-caption' => ['#default_value' => '']]];
    $form_state = new FormState();

    $this->loadMultipleThrowError = TRUE;
    $this->plugin->alterDialogForm($form, $form_state);
    $this->loadMultipleThrowError = FALSE;
    $this->assertEmpty($form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['image_style']['#options']);
    $form = [];
    $this->plugin->alterDialogForm($form, $form_state);
    $this->assertNotEmpty($form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['image_style']['#options']);
    $this->assertArrayNotHasKey('linkit', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);
  }

  public function testLinkitField() {
    $form = ['attributes' => ['data-caption' => ['#default_value' => '']]];
    $form_state = new FormState();

    $filter_format = $this->createMock(FilterFormatInterface::class);
    $filter_format->method('id')->willReturn('foo');
    $form_state->setBuildInfo(['args' => [$filter_format]]);

    $this->plugin->alterDialogForm($form, $form_state);
    $this->assertArrayNotHasKey('linkit', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);

    $this->linkitEnabled = TRUE;
    $this->plugin->alterDialogForm($form, $form_state);
    $this->assertArrayHasKey('linkit', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);
  }

  public function testFormValidation() {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'caption',
    ], FALSE);
    $this->plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'caption',
    ], ['value' => 'foo']);
    $this->plugin->validateDialogForm($form, $form_state);

    $this->assertEquals('{&quot;value&quot;:&quot;foo&quot;}', $form_state->getValue([
      'attributes',
      'data-caption',
    ]));
  }

  public function testValidateLinkit() {
    $element = [
      '#value' => $this->randomMachineName(),
      '#parents' => [],
    ];
    $form_state = new FormState();
    $this->plugin->validateLinkitHref($element, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());
  }

  public function testFormSubmit() {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'attributes' => [MediaEmbedDialogInterface::SETTINGS_KEY => []],
    ]);

    $this->plugin->submitDialogForm($form, $form_state);

    // Simplest form submit with no link or settings chosen.
    $this->assertArrayEquals([
      'place' => 1,
      'alt' => '',
    ], $form_state->getValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
    ]));

    $form_state->setValues([
      'attributes' => [
        MediaEmbedDialogInterface::SETTINGS_KEY => [
          'linkit' => [
            'href' => '/foo',
            'href_dirty_check' => '/bar',
            'data-entity-type' => 'node',
            'data-entity-uuid' => 'foo-bar-baz',
            'data-entity-substitution' => 'bar',
          ],
        ],
      ],
    ]);

    $this->plugin->submitDialogForm($form, $form_state);
    $values = $form_state->getValues();
    $this->assertArrayNotHasKey('data-entity-type', $values['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit']);
    $this->assertArrayHasKey('href', $values['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit']);
  }

  public function testPreRender() {
    $element = [
      '#media' => $this->mediaEntity,
      '#display_settings' => [
        'title_text' => 'Foo Title',
        'image_style' => 'foo',
        'linkit' => [
          'href' => '/foo/bar',
        ],
      ],
      'field_caption' => TRUE,
    ];
    $element = $this->plugin->preRender($element);
    $this->assertEquals('Foo Title', $element['field_foo'][0]['#item_attributes']['title']);
    $this->assertArrayNotHasKey('field_caption', $element);
    $this->assertInstanceOf(Url::class, $element['field_foo'][0]['#url']);

    $this->assertEquals('image', $element['field_foo']['#formatter']);
    $this->assertEquals('image_formatter', $element['field_foo'][0]['#theme']);
    $this->assertEquals('foo', $element['field_foo'][0]['#image_style']);
  }

  public function testProcessLinkitAutocomplete() {
    $element = [];
    $form_state = new FormState();
    $form = [];
    $this->plugin->processLinkitAutocomplete($element, $form_state, $form);
    $this->assertArrayEquals(['stanford_media/autocomplete'], $element['#attached']['library']);
  }

  public function loadMultipleCallback() {
    if ($this->loadMultipleThrowError) {
      throw new \Exception('It broke');
    }

    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('id')->willReturn('foo');
    $image_style->method('label')->willReturn('Foo');
    return [
      'foo' => $image_style,
    ];
  }

  public function loadCallback($entity_id) {
    if ($entity_id == 'image') {
      $media_type = $this->createMock(MediaTypeInterface::class);
      $media_type->method('getFieldMap')
        ->willReturn(['caption' => 'field_caption']);
      return $media_type;
    }
    $editor = $this->createMock(EditorInterface::class);
    $editor->method('getSettings')->willReturn([
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => $this->linkitEnabled,
          'linkit_profile' => 'default',
        ],
      ],
    ]);
    return $editor;
  }

}

/**
 * Class TestImage to make some protected methods public for easier testing.
 */
class TestImage extends Image {

  /**
   * {@inheritDoc}
   */
  public function getCaptionDefault(array $form, FormStateInterface $form_state) {
    return parent::getCaptionDefault($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public static function getLinkObject($link_path, array $link_options = []) {
    return parent::getLinkObject($link_path, $link_options);
  }

}
