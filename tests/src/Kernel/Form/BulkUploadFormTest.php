<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Form\BulkUpload;

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

    \Drupal::service('file_system')
      ->copy(__DIR__ . '/testfile.txt', 'temporary://testfile.txt', TRUE);
  }

  /**
   * Test form creation and validation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testForm() {
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $this->assertEmpty($media_storage->loadMultiple());

    $form_state = new FormState();

    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertArrayHasKey('upload', $form);
    $this->assertEmpty(Element::children($form['entities']));
    $this->checkAccess($form_state->getFormObject());

    $files = [['path' => 'temporary://testfile.txt']];
    $form_state->setValue(['upload', 'uploaded_files'], $files);

    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $form_state->setError($form, 'Not validate');
    $form_state->getFormObject()->validateForm($form, $form_state);
    $this->assertEmpty($media_storage->loadMultiple());

    $form_state->clearErrors();
    $form_state->getFormObject()->validateForm($form, $form_state);
    $this->assertNotEmpty($media_storage->loadMultiple());

    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertNotEmpty(Element::children($form['entities']));
  }

  /**
   * Test form submission works.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testFormSubmit() {
    $form_state = new FormState();
    $form_state->setMethod('GET');

    $files = [['path' => 'temporary://testfile.txt']];
    $form_state->setValue(['upload', 'uploaded_files'], $files);

    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $form_state->getFormObject()->validateForm($form, $form_state);

    $form_state->setTriggeringElement(['#eb_widget_main_submit' => TRUE]);
    $form_state->getFormObject()->submitForm($form, $form_state);
    $this->assertTrue($form_state->isRebuilding());

    $form = \Drupal::formBuilder()->rebuildForm($this->formArg, $form_state);
    $form_state->setTriggeringElement(['#eb_widget_main_submit' => FALSE]);
    $form_state->getFormObject()->submitForm($form, $form_state);

    $this->assertCount(1, \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadMultiple());

    // 2 files, since 1 is created when the module is enabled.
    $this->assertCount(2, \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadMultiple());
  }

  /**
   * Check account access results.
   *
   * @param \Drupal\stanford_media\Form\BulkUpload $form_object
   *   Form object.
   */
  protected function checkAccess(BulkUpload $form_object) {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);
    $this->assertFalse($form_object->access($account)->isAllowed());

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(TRUE);
    $this->assertTrue($form_object->access($account)->isAllowed());
  }

}
