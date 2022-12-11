<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormState;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialog\Media;

/**
 * Class MediaTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialog\File
 */
class MediaTest extends MediaEmbedDialogTestBase {

  /**
   * Dialog form should alter correctly.
   */
  public function testDialogAlter() {
    $plugin = Media::create($this->container, ['entity' => $this->mediaEntity], '', []);
    $this->assertTrue($plugin->isApplicable());

    $form = [];
    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => ['full' => 'Full', 'foo' => 'Foo', 'bar' => 'Bar'],
      '#default_value' => 'bar',
    ];

    $filter = $this->createMock(FilterInterface::class);
    $filter->method('getConfiguration')
      ->willReturn(['settings' => ['default_view_mode' => 'full']]);

    $filter_format = $this->createMock(FilterFormatInterface::class);
    $filter_format->method('filters')->willReturn($filter);

    $editor = $this->createMock(EditorInterface::class);
    $editor->method('getFilterFormat')->willReturn($filter_format);

    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$editor]);
    $plugin->alterDialogForm($form, $form_state);

    $this->assertEquals('hidden', $form['view_mode']['#type']);
    $this->assertCount(1, $form['view_mode']['#options']);
    $this->assertEquals('bar', $form['view_mode']['#value']);
  }

  /**
   * Display storage load callback.
   */
  public function loadCallback($display_id) {
    return NULL;
  }

}
