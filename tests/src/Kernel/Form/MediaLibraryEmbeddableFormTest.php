<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_library\MediaLibraryState;
use Drupal\stanford_media\Form\MediaLibraryEmbeddableForm;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\stanford_media\Kernel\StanfordMediaTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class MediaLibraryEmbeddableFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryEmbeddableForm
 */
class MediaLibraryEmbeddableFormTest extends StanfordMediaTestBase {

  use UserCreationTrait;

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'image',
    'media',
    'path_alias',
    'field',
    'file',
    'stanford_media',
  ];

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installConfig('media_library');

    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('file');
    //$this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

    $media_type = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $media_type->save();

    $media_type
      ->set('source_configuration', [
        'oembed_field_name' => 'field_media_embeddable_oembed',
        'unstructured_field_name' => 'field_media_embeddable_code',
        'thumbnails_directory' => 'public://oembed_thumbnails',
        'source_field' => 'field_media_embeddable_oembed',
      ])
      ->save();


    // Create the fields we need.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_oembed',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'oembed',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_code',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'unstructured',
    ])->save();


    $user = $this->createUser(['create embeddable media', 'view media']);
    $this->setCurrentUser($user);
  }

  /**
   * Media library add form should have the url input.
   */
  public function testMediaLibraryForm() {
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

    $this->assertArrayHasKey('field_media_embeddable_oembed', $form['container']);
    $this->assertArrayHasKey('field_media_embeddable_code', $form['container']);
    $this->assertArrayHasKey('submit', $form['container']);

    /** @var \Drupal\stanford_media\Form\MediaLibraryEmbeddableForm $form_object */
    $form_object = $form_state->getFormObject();
    $form_object->buildForm($form, $form_state);

    $this->assertEmpty($form_state->get('current_selection'));
    $form_state->setValue('field_media_embeddable_code', '<iframe src="http://www.test.com"></iframe>');
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
    \Drupal::formBuilder()->buildForm(MediaLibraryEmbeddableForm::class, $form_state);
  }

}
