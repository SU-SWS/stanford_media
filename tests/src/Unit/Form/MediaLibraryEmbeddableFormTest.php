<?php

namespace Drupal\Tests\stanford_media\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
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
    'field_permissions',
    'stanford_media',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Urls should validate correctly.
   */
  public function testFormValidation() {
    $form_object = new TestMediaLibraryEmbeddableForm();
    $form_state = new FormState();
    $form = [];

    $form_state->setValue($form_object->unstructuredField, ['<iframe src="http://www.test.com"></iframe>']);
    $form_object->validateEmbeddable($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
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

  /**
   * {@inheritDoc}
   */
  public function __construct() {
  }

}
