<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\SoundCloud;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Class SoundCloud.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\SoundCloud
 */
class SoundCloudTest extends MediaEmbedDialogTestBase {

  /**
   * The url of the media field.
   *
   * @var string
   */
  protected $mediaEntityUrl = 'http://soundcloud.com/foo/bar/baz';

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mediaBundle = 'audio';
  }

  public function testApplication() {
    $plugin = SoundCloud::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $this->assertTrue($plugin->isApplicable());

    $this->mediaBundle = 'file';
    $this->assertFalse($plugin->isApplicable());

    $this->mediaBundle = 'audio';
    $this->mediaEntityUrl = $this->randomMachineName();
    $this->assertFalse($plugin->isApplicable());
  }

  public function testAlterDialog() {
    $plugin = SoundCloud::create($this->container, ['entity' => $this->mediaEntity], '', []);

    $form = ['attributes' => ['data-caption' => ['#type' => 'hidden']]];
    $form_state = new FormState();
    $display_settings = ['style' => 'foo_bar'];
    $form_state->setUserInput(['editor_object' => [MediaEmbedDialogInterface::SETTINGS_KEY => json_encode($display_settings)]]);

    $plugin->alterDialogForm($form, $form_state);
    $this->assertEquals('textfield', $form['attributes']['data-caption']['#type']);
    $this->assertEquals('radios', $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['style']['#type']);
  }

  public function testPreRender() {
    $plugin = SoundCloud::create($this->container, ['entity' => $this->mediaEntity], '', []);

    $element = [
      '#display_settings' => ['style' => 'classic'],
      '#media' => $this->mediaEntity,
    ];
    $element = $plugin->preRender($element);
    $this->assertEquals('false', $element['field_foo'][0]['children']['#query']['visual']);
  }

  public function fieldGetStringCallback() {
    return $this->mediaEntityUrl;
  }

}
