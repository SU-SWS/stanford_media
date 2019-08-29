<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;

/**
 * Class BulkUploadFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\BulkUpload
 */
class BulkUploadFormTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'stanford_media',
    'field',
    'file',
    'image',
    'dropzonejs',
    'user',
    'media',
  ];

  /**
   * Testing form namespace argument.
   *
   * @var string
   */
  protected $formArg = '\Drupal\stanford_media\Form\BulkUpload';

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);

    $media_type = MediaType::create([
      'name' => 'file',
      'id' => 'file',
      'source' => 'file',
    ]);
    $media_type->save();
    // Create the source field.
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', ['source_field' => $source_field->getName()])
      ->save();
  }

  public function testForm() {
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $this->assertEmpty($media_storage->loadMultiple());

    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertArrayHasKey('upload', $form);
    $this->assertEmpty(Element::children($form['entities']));

    file_unmanaged_copy(__DIR__ . '/testfile.txt', 'temporary://testfile.txt');
    $files = [
      ['path' => 'temporary://testfile.txt'],
    ];
    $form_state->setValue(['upload', 'uploaded_files'], $files);
    $form_state->getFormObject()->validateForm($form, $form_state);

    $this->assertNotEmpty($media_storage->loadMultiple());
    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertNotEmpty(Element::children($form['entities']));
  }

}
