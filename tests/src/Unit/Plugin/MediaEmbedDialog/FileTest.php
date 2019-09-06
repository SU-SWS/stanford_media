<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\File;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Class File.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\File
 */
class FileTest extends MediaEmbedDialogTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mediaBundle = 'file';
  }

  /**
   * Test the plugin is applicable.
   */
  public function testPluginApplication() {
    $plugin = File::create($this->container, ['entity' => new \stdClass()], '', []);
    $this->assertFalse($plugin->isApplicable());

    $plugin = File::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $this->assertTrue($plugin->isApplicable());

    $this->mediaBundle = 'image';
    $this->assertFalse($plugin->isApplicable());

    $form = [];
    $form_state = new FormState();
    $form_state->clearErrors();
    $this->assertNull($plugin->validateDialogForm($form, $form_state));
    $this->assertFalse($form_state::hasAnyErrors());
    $this->assertNull($plugin->submitDialogForm($form, $form_state));
    $this->assertNull($plugin->embedAlter($form, $this->mediaEntity, $form));
  }

  /**
   * Dialog form should alter correctly.
   */
  public function testDialogAlter() {
    $plugin = File::create($this->container, ['entity' => $this->mediaEntity], '', []);

    $form = ['attributes' => ['data-caption' => ['#type' => 'textfield']]];
    $form_state = new FormState();
    $display_settings = ['description' => 'foo bar'];
    $form_state->setUserInput(['editor_object' => [MediaEmbedDialogInterface::SETTINGS_KEY => json_encode($display_settings)]]);

    $plugin->alterDialogForm($form, $form_state);
    $this->assertArrayHasKey('description', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);
    $this->assertEquals('foo bar', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['description']['#default_value']);
    $this->assertEquals('hidden', $form['attributes']['data-caption']['#type']);
  }

  /**
   * PreRender should set the field value.
   */
  public function testPreRender() {
    $plugin = File::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $element = [
      '#media' => $this->mediaEntity,
      '#display_settings' => ['description' => 'foo bar'],
    ];
    $element = $plugin->preRender($element);
    $this->assertArrayHasKey('field_foo', $element);
    $this->assertEquals('foo bar', $element['field_foo'][0]['#description']);
  }

}
