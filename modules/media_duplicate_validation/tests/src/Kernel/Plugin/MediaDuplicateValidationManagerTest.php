<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin;

use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5;

/**
 * Class MediaDuplicateValidationManagerTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
 */
class MediaDuplicateValidationManagerTest extends MediaDuplicateValidationTestBase {

  /**
   * Test similar entities.
   *
   * @covers ::getSimilarEntities
   */
  public function testSimilarEntities() {
    $this->assertNotEmpty($this->duplicationManager->getSimilarEntities($this->mediaEntity));
  }

  /**
   * Validate the table is removed.
   */
  public function testRemovingSchema() {
    $this->assertTrue(\Drupal::database()
      ->schema()
      ->tableExists(Md5::DATABASE_TABLE));

    $this->duplicationManager->removeSchemas('md5');

    $this->assertFalse(\Drupal::database()
      ->schema()
      ->tableExists(Md5::DATABASE_TABLE));
  }

}
