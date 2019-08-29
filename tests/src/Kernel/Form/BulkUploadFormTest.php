<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class BulkUploadFormTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\BulkUpload
 */
class BulkUploadFormTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'stanford_media',
    'image',
    'file',
    'dropzonejs',
    'user',
    'media',
  ];

  /**
   * Testing form namespace argument.
   *
   * @var string
   */
  protected $formArg = '\Drupal\stanford_media\Form\BulkUpload';

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  public function testForm(){
    $form = \Drupal::formBuilder()->getForm($this->formArg);

  }

}
