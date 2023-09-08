<?php

namespace Drupal\Tests\stanford_media\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Form\MediaLibraryGoogleFormForm;
use Drupal\Tests\UnitTestCase;

/**
 * Class MediaLibraryGoogleFormFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\MediaLibraryGoogleFormForm
 */
class MediaLibraryGoogleFormFormTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Urls should validate correctly.
   */
  public function testUrlValidate() {
    $form_object = new TestMediaLibraryGoogleFormForm();
    $form_state = new FormState();
    $form = [];

    $form_state->setValue('url', 'foo-bar');
    $form_object->validateUrl($form, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    $form_state->clearErrors();

    $form_state->setValue('url', 'http://google.com/forms/a/b/c/viewform');
    $form_object->validateUrl($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
  }

}

/**
 * Testable object.
 */
class TestMediaLibraryGoogleFormForm extends MediaLibraryGoogleFormForm {

  /**
   * {@inheritDoc}
   */
  public function __construct() {
  }

}
