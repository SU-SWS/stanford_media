<?php

namespace Drupal\Tests\stanford_media\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Tests for the stanford media service.
 *
 * @coversDefaultClass \Drupal\stanford_media\StanfordMedia
 */
class StanfordMediaTest extends StanfordMediaTestBase {

  /**
   * Media deletion should delete the associated file from the server.
   */
  public function testFileDelete() {

    $source_field = $this->mediaType->getSource()
      ->getSourceFieldDefinition($this->mediaType)
      ->getName();

    $file_uri = 'temporary://testfile.txt';
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create(['uri' => $file_uri]);
    $file->save();
    $media = Media::create([
      'bundle' => 'file',
      $source_field => ['target_id' => $file->id()],
    ]);
    $media->save();
    $this->assertTrue(file_exists($file->getFileUri()));

    $media->delete();
    $this->assertFalse(file_exists($file->getFileUri()));
  }

}
