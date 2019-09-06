<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\YoutubeVideo;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Class YoutubeVideo.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\YoutubeVideo
 */
class YoutubeVideoTest extends MediaEmbedDialogTestBase {

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
    $plugin = YoutubeVideo::create($this->container, ['entity' => $this->mediaEntity], '', ['video_provider' => 'bar']);
    $this->assertFalse($plugin->isApplicable());

    $plugin = YoutubeVideo::create($this->container, ['entity' => $this->mediaEntity], '', ['video_provider' => 'foo']);
    $this->assertTrue($plugin->isApplicable());

    $default_input = [
      'start' => 0,
      'rel' => 0,
      'showinfo' => 1,
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
    $plugin = YoutubeVideo::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $form = ['attributes' => ['data-align' => []]];
    $form_state = new FormState();

    $user_input = ['start' => '121'];
    $form_state->setUserInput([
      'editor_object' => [
        MediaEmbedDialogInterface::SETTINGS_KEY => json_encode($user_input),
      ],
    ]);

    $plugin->alterDialogForm($form, $form_state);

    $this->assertArrayNotHasKey('data-align', $form['attributes']);
    $this->assertCount(6, $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]);
    $this->assertEquals('02:01', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['start']['#default_value']);
  }

  /**
   * @covers ::validateDialogForm
   */
  public function testFormValidate() {
    $plugin = YoutubeVideo::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $form = [];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['start']['#parents'] = [];
    $form_state = new FormState();
    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ], '34');

    $plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ], 'a:b');

    $plugin->validateDialogForm($form, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    $form_state->clearErrors();
    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ], '1:12');

    $plugin->validateDialogForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
    $this->assertEquals('72', $form_state->getValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ]));
  }

  /**
   * Test the preRender method.
   */
  public function testPreRender() {
    $plugin = YoutubeVideo::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $element = [
      '#media' => $this->mediaEntity,
      '#display_settings' => [
        'class' => 'foo bar',
        'start' => 72,
      ],
    ];
    $element = $plugin->preRender($element);
    $this->assertEquals(72, $element['field_foo'][0]['children']['#query']['start']);
  }

}
