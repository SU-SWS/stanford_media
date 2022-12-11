<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\File;

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
  public function setup(): void {
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
    $plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
  }

  /**
   * Dialog form should alter correctly.
   */
  public function testDialogAlter() {
    $plugin = File::create($this->container, ['entity' => $this->mediaEntity], '', []);

    $form = ['attributes' => ['data-caption' => ['#type' => 'textfield']]];
    $form_state = new FormState();
    $user_input = ['editor_object' => ['attributes' => ['data-display-description' => 'foo bar']]];
    $form_state->setUserInput($user_input);
    $plugin->alterDialogForm($form, $form_state);
    $this->assertArrayHasKey('description', $form);
    $this->assertEquals('foo bar', $form['description']['#default_value']);
  }

  /**
   * PreRender should set the field value.
   */
  public function testPreRender() {
    $plugin = File::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $build = [
      '#media' => $this->mediaEntity,
      '#attributes' => ['data-display-description' => 'foo bar'],
    ];
    $plugin->embedAlter($build, $this->mediaEntity);
    $this->assertArrayHasKey('field_foo', $build);
    $this->assertEquals('foo bar', $build['field_foo'][0]['#description']);
  }

}
