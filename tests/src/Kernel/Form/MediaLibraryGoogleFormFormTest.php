<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_library\MediaLibraryState;
use Drupal\stanford_media\Form\MediaLibraryGoogleFormForm;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class MediaLibraryGoogleFormFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryGoogleFormForm
 */
class MediaLibraryGoogleFormFormTest extends StanfordMediaFormTestBase {

  use UserCreationTrait;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('media_library');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');

    $media_type = MediaType::create([
      'id' => 'google_form',
      'label' => 'google_form',
      'source' => 'google_form',
    ]);

    $media_type->save();

    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type
      ->set('source_configuration', [
        'source_field' => $source_field->getName(),
        'height_field_name' => 'field_media_google_form_hgt',
      ])->save();

    // Create the field we need.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_google_form_hgt',
      'entity_type' => 'media',
      'type' => 'integer',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'google_form',
      'label' => 'Form Height',
    ])->save();

    $user = $this->createUser(['create google_form media', 'view media']);
    $this->setCurrentUser($user);
  }

  /**
   * Media library add form should have the url input.
   */
  public function testMediaLibraryForm() {
    $params = [
      'media_library_opener_id' => 'foo-bar',
      'media_library_allowed_types' => ['google_form'],
      'media_library_selected_type' => 'google_form',
      'media_library_remaining' => 1,
      'media_library_content' => '1',
    ];
    $state = new MediaLibraryState($params);

    $form_state = new FormState();
    $form_state->set('media_library_state', $state);
    $form = \Drupal::formBuilder()->buildForm(MediaLibraryGoogleFormForm::class, $form_state);

    $this->assertArrayHasKey('url', $form['container']);
    $this->assertArrayHasKey('submit', $form['container']);

    /** @var \Drupal\stanford_media\Form\MediaLibraryGoogleFormForm $form_object */
    $form_object = $form_state->getFormObject();
    $form_object->buildForm($form, $form_state);

    $this->assertEmpty($form_state->get('current_selection'));
    $form_state->setValue('url', 'http://google.com');
    $form_object->addButtonSubmit($form, $form_state);
    $this->assertCount(1, $form_state->get('media'));
  }

  /**
   * Building the form for the incorrect media type should throw an error.
   */
  public function testFormException() {
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
    \Drupal::formBuilder()->buildForm(MediaLibraryGoogleFormForm::class, $form_state);
  }

}
