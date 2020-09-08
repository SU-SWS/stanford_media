<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_library\MediaLibraryState;
use Drupal\stanford_media\Form\MediaLibraryEmbeddableForm;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class MediaLibraryEmbeddableFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryEmbeddableForm
 */
class MediaLibraryEmbeddableFormTest extends StanfordMediaFormTestBase {

  use UserCreationTrait;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    /*
    parent::setUp();
    $this->installConfig('media_library');
    $this->installSchema('system', ['sequences']);

    $media_type = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', ['source_field' => $source_field->getName()])->save();

    $user = $this->createUser(['create embeddable media', 'view media']);
    $this->setCurrentUser($user);
    */
  }

  /**
   * Media library add form should have the url input.
   */
  public function testMediaLibraryForm() {
    /*
    $params = [
      'media_library_opener_id' => 'foo-bar',
      'media_library_allowed_types' => ['embeddable'],
      'media_library_selected_type' => 'embeddable',
      'media_library_remaining' => 1,
      'media_library_content' => '1',
    ];
    $state = new MediaLibraryState($params);

    $form_state = new FormState();
    $form_state->set('media_library_state', $state);
    $form = \Drupal::formBuilder()->buildForm(MediaLibraryEmbeddableForm::class, $form_state);

    $this->assertArrayHasKey('url', $form['container']);
    $this->assertArrayHasKey('submit', $form['container']);
    */

    /** @var \Drupal\stanford_media\Form\MediaLibraryEmbeddableForm $form_object */

    /*$form_object = $form_state->getFormObject();
    $form_object->buildForm($form, $form_state);

    $this->assertEmpty($form_state->get('current_selection'));
    $form_state->setValue('url', 'http://google.com');
    $form_object->addButtonSubmit($form, $form_state);
    $this->assertCount(1, $form_state->get('media'));
    */
  }

  /**
   * Building the form for the incorrect media type should throw an error.
   */
  public function testFormException() {
    /*
    $params = [
      'media_library_opener_id' => 'foo-bar',
      'media_library_allowed_types' => ['file'],
      'media_library_selected_type' => 'file',
      'media_library_remaining' => 1,
      'media_library_content' => '1',
    ];
    $state = new MediaLibraryState($params);

    $form_state = new FormState();
    $form_state->set('media_library_state', $state);
    $this->expectException('\InvalidArgumentException');
    \Drupal::formBuilder()->buildForm(MediaLibraryEmbeddableForm::class, $form_state);
    */
  }

}
