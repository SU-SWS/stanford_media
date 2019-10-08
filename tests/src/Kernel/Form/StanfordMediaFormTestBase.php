<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;

abstract class StanfordMediaFormTestBase extends KernelTestBase {

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
  ];

  /**
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('system');

    $this->mediaType = MediaType::create([
      'name' => 'file',
      'id' => 'file',
      'source' => 'file',
    ]);
    $this->mediaType->save();
    // Create the source field.
    $source_field = $this->mediaType->getSource()->createSourceField($this->mediaType);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $this->mediaType->set('source_configuration', ['source_field' => $source_field->getName()])
      ->save();

    \Drupal::service('file_system')
      ->copy(__DIR__ . '/testfile.txt', 'temporary://testfile.txt', TRUE);
  }

}
