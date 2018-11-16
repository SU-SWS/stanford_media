<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidation;

use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5;

/**
 * Class ColorMeanTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5
 *
 * @group media_duplicate_validation
 */
class Md5Test extends ValidationTestBase {

  /**
   * @covers ::schema
   */
  public function testDatabase() {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists(Md5::DATABASE_TABLE));
    $this->assertTrue($schema->fieldExists(Md5::DATABASE_TABLE, 'mid'));
    $this->assertTrue($schema->fieldExists(Md5::DATABASE_TABLE, 'md5'));
  }

  /**
   * @covers ::mediaSave
   * @covers ::mediaDelete
   */
  public function testMediaSaveDelete() {
    $mid = $this->mediaEntity->id();
    $this->assertNotEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)
      ->execute()
      ->fetchAssoc());
    $this->mediaEntity->delete();
    $this->assertEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)
      ->execute()
      ->fetchAssoc());
  }

  /**
   * @covers ::getSimilarItems
   */
  public function testSimilarItems() {

  }

}
