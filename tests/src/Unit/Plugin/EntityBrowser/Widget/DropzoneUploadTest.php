<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormState;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\file\FileInterface;
use Drupal\stanford_media\Plugin\EntityBrowser\Widget\DropzoneUpload;

/**
 * Class DropzoneUploadTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EntityBrowser\Widget\DropzoneUpload
 */
class DropzoneUploadTest extends EntityBrowserWidgetTestBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $file_entity = $this->createMock(FileInterface::class);

    $dropzone_save = $this->createMock(DropzoneJsUploadSaveInterface::class);
    $dropzone_save->method('createFile')->willReturn($file_entity);

    $this->container->set('dropzonejs.upload_save', $dropzone_save);
    $this->plugin = DropzoneUpload::create($this->container, ['settings' => ['entity_browser_id' => $this->randomMachineName()]], '', ['auto_select' => FALSE]);
  }

  /**
   * Form structure.
   */
  public function testConfigForm() {
    $form = [];
    $form_state = new FormState();
    $form = $this->plugin->buildConfigurationForm($form, $form_state);
    $this->assertCount(3, $form);
    $this->assertArrayHasKey('upload_location', $form);
    $this->assertArrayHasKey('dropzone_description', $form);
  }

  /**
   * Preparing the entities gives correct counts and values saved.
   */
  public function testPrepareEntities() {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue(['upload', 'uploaded_files'], [
      ['path' => __FILE__],
      ['path' => __FILE__],
      ['path' => __FILE__],
    ]);
    $entities = $this->plugin->prepareEntities($form, $form_state);
    $this->assertCount(3, $entities);

    $this->assertNotEmpty($entities);
    $this->assertEquals($entities, $form_state->get([
      'dropzonejs',
      $this->plugin->uuid(),
      'media',
    ]));

    $this->assertEquals($entities, $this->plugin->prepareEntities($form, $form_state));

    $form_state->set(['dropzonejs', $this->plugin->uuid(), 'media'], []);
    $form['widget']['upload']['#max_files'] = 2;
    $entities = $this->plugin->prepareEntities($form, $form_state);
    $this->assertCount(2, $entities);
  }

  /**
   * Form structure is correct.
   */
  public function testForm() {
    $original_form = [];
    $widget_params = [];
    $form_state = new FormState();
    $form_state->set([
      'entity_browser',
      'widget_context',
      'target_bundles',
    ], ['file']);

    $form = $this->plugin->getForm($original_form, $form_state, $widget_params);
    $this->assertArrayHasKey('upload', $form);
    $this->assertEquals('dropzonejs', $form['upload']['#type']);

    $form_state->set([
      'dropzonejs',
      $this->plugin->uuid(),
      'media',
    ], [$this->getMockMediaEntity()]);
    $form = $this->plugin->getForm($original_form, $form_state, $widget_params);
    $this->assertEquals('hidden', $form['upload']['#type']);
  }

}
