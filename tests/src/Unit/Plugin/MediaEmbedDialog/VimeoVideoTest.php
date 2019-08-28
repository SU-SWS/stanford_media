<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\VimeoVideo;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Class VimeoVideo.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\VimeoVideo
 */
class VimeoVideoTest extends MediaEmbedDialogTestBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mediaBundle = 'video';
  }

  /**
   * Application and default input of the plugin.
   */
  public function testApplication() {
    $plugin = VimeoVideo::create($this->container, ['entity' => $this->mediaEntity], '', ['video_provider' => 'bar']);
    $this->assertFalse($plugin->isApplicable());

    $plugin = VimeoVideo::create($this->container, ['entity' => $this->mediaEntity], '', ['video_provider' => 'foo']);
    $this->assertTrue($plugin->isApplicable());

    $default_input = [
      'title' => 1,
      'byline' => 1,
      'color' => '',
      'autoplay' => 0,
      'loop' => 0,
      'class' => '',
    ];
    $this->assertarrayEquals($default_input, $plugin->getDefaultInput());
  }

  /**
   * Test dialog alter and validation.
   */
  public function testAlterDialog() {
    $plugin = VimeoVideo::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $form = ['attributes' => ['data-align' => []]];
    $form_state = new FormState();
    $plugin->alterDialogForm($form, $form_state);

    $this->assertArrayNotHasKey('data-align', $form['attributes']);
    $this->assertCount(6, $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);

    $setting_keys = ['intro', 'autoplay', 'loop', 'title', 'byline', 'color'];

    foreach ($setting_keys as $key) {
      $this->assertArrayHasKey($key, $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);
    }

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['color']['#parents'] = [];

    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'color',
    ], 'blue');
    $plugin->validateDialogForm($form, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    $form_state->clearErrors();
    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'color',
    ], 'ab12bc99');
    $plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
  }

  /**
   * Prerender should alter the element correctly.
   */
  public function testPreRender() {
    $plugin = VimeoVideo::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $element = [
      '#media' => $this->mediaEntity,
      '#display_settings' => [
        'color' => 'blue',
        'autoplay' => 1,
        'class' => 'foo bar',
      ],
    ];
    $element = $plugin->preRender($element);
    $this->assertArraySubset([
      'foo',
      'bar',
    ], $element['field_foo'][0]['#attributes']['class']);
    $this->assertEquals(1, $element['field_foo'][0]['children']['#query']['autoplay']);
  }

}
