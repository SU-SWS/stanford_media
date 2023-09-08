<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidation;

use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidationTestBase;

/**
 * Class ColorMeanTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5
 *
 * @group media_duplicate_validation
 */
class Md5Test extends MediaDuplicateValidationTestBase {

  /**
   * Plugin instance.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->plugin = $this->duplicationManager->createInstance('md5');
  }

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
    $path = 'public://logo2.png';
    copy(__DIR__ . '/../assets/logo2.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ])->save();
    $this->assertNotEmpty($this->plugin->getSimilarItems($this->mediaEntity));
  }

}
