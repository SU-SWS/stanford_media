<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\user\Traits\UserCreationTrait;

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

    $media_type = MediaType::create([
      'id' => 'google_form',
      'label' => 'google_form',
      'source' => 'google_form',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', ['source_field' => $source_field->getName()])->save();

    $user = $this->createUser(['create google_form media']);
    $this->setCurrentUser($user);
  }

  public function testMediaLibraryForm() {
    $params = [
      'media_library_opener_id' => 'foo-bar',
      'media_library_allowed_types' => ['google_form'],
      'media_library_selected_type' => 'google_form',
      'media_library_remaining' => 1,
      'media_library_content' => '1',
    ];
    $state = new MediaLibraryState($params);
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = \Drupal::service('media_library.ui_builder');
    $ui = $ui_builder->buildUi($state);

    $this->assertArrayHasKey('url', $ui['form']['container']);
    $this->assertArrayHasKey('submit', $ui['form']['container']);
  }

}
