<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\Image;
use Drupal\media\Plugin\media\Source\Image as ImageSource;

/**
 * Class Image.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\Image
 */
class ImageTest extends MediaEmbedDialogTestBase {

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->mediaBundle = 'image';
    $this->mediaSource = $this->createMock(ImageSource::class);
  }

  /**
   * Test the plugin is applicable.
   */
  public function testPluginApplication() {
    $plugin = Image::create($this->container, ['entity' => new \stdClass()], '', []);
    $this->assertFalse($plugin->isApplicable());

    $plugin = Image::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $this->assertTrue($plugin->isApplicable());

    $form = [];
    $form_state = new FormState();
    $form_state->clearErrors();
    $plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
  }

  /**
   * Dialog form should alter correctly.
   */
  public function testDialogAlter() {
    $plugin = Image::create($this->container, ['entity' => $this->mediaEntity], '', []);

    $form = ['attributes' => ['data-caption' => ['#type' => 'textfield']]];
    $form_state = new FormState();
    $user_input = ['editor_object' => ['attributes' => ['data-caption' => 'foo bar']]];
    $form_state->setUserInput($user_input);
    $plugin->alterDialogForm($form, $form_state);

    $this->assertArrayNotHasKey('caption_text', $form);

    $form['caption'] = [];
    $plugin->alterDialogForm($form, $form_state);
    $this->assertArrayHasKey('caption_text', $form);
    $this->assertEquals('foo bar', $form['caption_text']['#default_value']);
  }

  /**
   * Alter dialog values will strip html tags except some.
   */
  public function testAlterDialogValues() {
    $form = [];
    $plugin = Image::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $values = [];
    $form_state = new FormState();
    $form_state->setValues([
      'hasCaption' => 1,
      'caption_text' => '<div>Foo <a href="/here">Bar</a> Baz</div>',
    ]);
    $plugin->alterDialogValues($values, $form, $form_state);

    $this->assertEquals([
      'attributes' => [
        'data-caption' => 'Foo <a href="/here">Bar</a> Baz',
        'data-caption-hash' => 'badc6',
      ],
    ], $values);
  }

  /**
   * PreRender should set the field value.
   */
  public function testPreRender() {
    $field_item = $this->createMock(TypedDataInterface::class);
    $field_item->method('getString')
      ->willReturn(Image::DECORATIVE);
    $image_item = $this->createMock(ImageItem::class);
    $image_item->method('get')->willReturn($field_item);

    $this->mediaSource->method('getConfiguration')
      ->willReturn(['source_field' => 'field_foo']);
    $plugin = Image::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $build = [
      '#media' => $this->mediaEntity,
      '#attributes' => ['data-caption-hash' => 'foo bar'],
      'field_foo' => [['#item' => $image_item]],
    ];
    $plugin->embedAlter($build, $this->mediaEntity);
    $this->assertArrayNotHasKey('data-caption-hash', $build['#attributes']);
  }

}
