<?php

namespace Drupal\Tests\stanford_media\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_media\Form\MediaLibraryEmbeddableForm;
use Drupal\Tests\UnitTestCase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class MediaLibraryEmbeddableFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryEmbeddableForm
 */
class MediaLibraryEmbeddableFormTest extends UnitTestCase {

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
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }


  public function testGetFormId() {
    $form_object = new TestMediaLibraryEmbeddableForm();
    $this->assertStringContainsString('_embeddable', $form_object->getFormId());
  }

  public function testIsUnstructured() {
    $form_object = new TestMediaLibraryEmbeddableForm();
    $form_state = new FormState();
    $form = [];
    $this->assertFalse($form_object->isUnstructured($form_state));
  }

}

/**
 * Testable object.
 */
class TestMediaLibraryEmbeddableForm extends MediaLibraryEmbeddableForm {

  public function __construct() {
  }

  /**
   * We override this so unit tests work without a full form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function setFieldNames(FormStateInterface $form_state): void {
    $this->oEmbedField = 'field_media_embeddable_oembed';
    $this->unstructuredField = 'field_media_embeddable_unstructured';
  }

}
