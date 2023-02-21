<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormState;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Drupal\media_library\MediaLibraryState;
use Drupal\stanford_media\Form\MediaLibraryFileUploadForm;
use Drupal\Tests\stanford_media\Kernel\StanfordMediaTestBase;

/**
 * Class MediaLibraryFileUploadFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryFileUploadForm
 */
class MediaLibraryFileUploadFormTest extends StanfordMediaTestBase {

  /**
   * Testing form namespace argument.
   *
   * @var string
   */
  protected $formArg = 'Drupal\Tests\stanford_media\Kernel\Form\TestForm';

  /**
   * Add the media duplication service to drupal container.
   */
  protected function addDuplicationService() {
    $similarities = [
      $this->createMedia(),
      $this->createMedia(),
      $this->createMedia(),
    ];

    $duplication_service = $this->createMock(MediaDuplicateValidationManager::class);
    $duplication_service->method('getSimilarEntities')
      ->willReturn($similarities);
    \Drupal::getContainer()
      ->set('plugin.manager.media_duplicate_validation', $duplication_service);
  }

  /**
   * Create a media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMedia() {
    $entity = Media::create([
      'bundle' => 'file',
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * Test the basic form and state structure.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testFormStructure() {
    $form_state = new FormState();
    $media_library_state = new MediaLibraryState([
      'media_library_opener_id' => $this->randomMachineName(),
      'media_library_allowed_types' => ['file'],
      'media_library_selected_type' => 'file',
      'media_library_remaining' => -1,
    ]);

    $form_state->set('media_library_state', $media_library_state);
    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertCount(30, $form);
    $this->assertArrayHasKey('dropzone', $form['container']);

    /** @var \Drupal\stanford_media\Form\MediaLibraryFileUploadForm $form_object */
    $form_object = $form_state->getFormObject();

    $form_state->setValue([
      'dropzone',
      'uploaded_files',
    ], [['path' => 'temporary://testfile.txt']]);
    $form_state->setTriggeringElement([
      '#parents' => [],
      '#ajax' => ['wrapper' => 'foo'],
    ]);

    $this->assertCount(0, File::loadMultiple());
    $form_object->uploadDropzoneSubmit($form, $form_state);
    $this->assertCount(1, File::loadMultiple());

    $this->assertEmpty($form_state->getValue('upload'));
    $form_object->validateUploadElement($form['container']['upload'], $form_state);
    $this->assertArrayHasKey('fids', $form_state->getValue('upload'));
  }

  /**
   * Test the entity form without duplication service.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testEntityFormElement() {
    [$form, $form_state] = $this->runEntityFormSetup();
    $this->assertArrayNotHasKey('similar_media', $form['media'][0]);
  }

  /**
   * Test the entity form with duplication service.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testEntityFormWithSimilar() {
    $this->addDuplicationService();
    [$form, $form_state] = $this->runEntityFormSetup();
    $this->assertCount(4, $form['media'][0]['similar_media'][0]['#options']);
  }

  /**
   * Build the entity form with a form state.
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  protected function runEntityFormSetup() {
    $source_field = $this->mediaType->getSource()
      ->getSourceFieldDefinition($this->mediaType);

    $file = File::create(['uri' => 'temporary://testfile.txt']);
    $file->save();
    $media = Media::create([
      'bundle' => 'file',
      $source_field->getName() => $file->id(),
    ]);

    $form_state = new FormState();
    $media_library_state = new MediaLibraryState([
      'media_library_opener_id' => $this->randomMachineName(),
      'media_library_allowed_types' => ['file'],
      'media_library_selected_type' => 'file',
      'media_library_remaining' => 1,
    ]);

    $form_state->set('media', [$media]);
    $form_state->set('media_library_state', $media_library_state);
    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    return [$form, $form_state];
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testValidation() {
    $this->addDuplicationService();
    /** @var \Drupal\Core\Form\FormStateInterface $form_state */
    [$form, $form_state] = $this->runEntityFormSetup();
    $form_state->setValue(['similar_media'], [2]);
    $form_state->getFormObject()->validateForm($form, $form_state);
    $this->assertEquals(2, $form_state->get(['media', 0])->id());
  }

}

/**
 * Class TestForm.
 */
class TestForm extends MediaLibraryFileUploadForm {

  /**
   * {@inheritDoc}
   */
  protected static function getRenderDisplay(array &$render_array): MarkupInterface|string {
    return implode(', ', array_keys($render_array));
  }

}
