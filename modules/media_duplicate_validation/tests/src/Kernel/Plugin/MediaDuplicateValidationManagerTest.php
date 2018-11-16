<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin;

/**
 * Class MediaDuplicateValidationManagerTest
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
 */
class MediaDuplicateValidationManagerTest extends MediaDuplicateValidationTestBase {

  /**
   * @covers ::getSimilarEntities
   */
  public function testSimlarEntities() {
    $this->assertNotEmpty($this->duplicationManager->getSimilarEntities($this->mediaEntity));
  }

}
