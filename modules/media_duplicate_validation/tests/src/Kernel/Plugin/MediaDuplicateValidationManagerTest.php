<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin;

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
  public function testSimlarEntities() {
    $this->assertNotEmpty($this->duplicationManager->getSimilarEntities($this->mediaEntity));
  }

}
