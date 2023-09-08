<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\Media;
use Drupal\Tests\stanford_media\Kernel\StanfordMediaTestBase;

/**
 * Class StanfordMediaDialogFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\StanfordMediaDialogForm
 */
class StanfordMediaDialogFormTest extends StanfordMediaTestBase {

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
    'media_library',
    'views',
    'editor',
    'ckeditor5',
    'filter',
  ];

  /**
   * Testing form namespace argument.
   *
   * @var string
   */
  protected $formArg = 'Drupal\stanford_media\Form\StanfordMediaDialogForm';

  /**
   * Test form structure.
   */
  public function testFormStructure() {
    FilterFormat::create(['format' => 'html', 'name' => 'test format'])->save();
    $editor = Editor::create([
      'format' => 'html',
      'editor' => 'ckeditor5',
    ]);
    $editor->save();
    $media = Media::create(['bundle' => 'file']);
    $media->save();
    $form_state = new FormState();
    $form_state->set('media_embed_element', [
      'data-entity-uuid' => $media->uuid(),
      'data-view-mode' => NULL,
    ]);
    $form_state->addBuildInfo('args', [$editor]);
    $form = \Drupal::formBuilder()->buildForm($this->formArg, $form_state);
    $this->assertArrayHasKey('description', $form);

    $form_state->setValue(['attributes', 'data-view-mode'], NULL);
    $form_state->setValue('description', 'foo bar');
    $form_state->getFormObject()->validateForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
    $response = $form_state->getFormObject()->submitForm($form, $form_state);
    $this->assertEquals('foo bar', $response->getCommands()[0]['values']['attributes']['data-display-description']);
  }

}
