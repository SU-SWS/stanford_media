<?php

namespace Drupal\Tests\stanford_media\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Form\MediaLibraryEmbeddableForm;
use Drupal\Tests\UnitTestCase;

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
