<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidation;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean;
use Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidationTestBase;

/**
 * Class ColorMeanTest.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\ColorMean
 *
 * @group media_duplicate_validation
 */
class ColorMeanTest extends MediaDuplicateValidationTestBase {

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
    $this->plugin = $this->duplicationManager->createInstance('color_mean');
  }

  /**
   * @covers ::schema
   */
  public function testDatabase() {
    $schema = \Drupal::database()->schema();

    $this->assertTrue($schema->tableExists(ColorMean::DATABASE_TABLE));
    for ($i = 1; $i <= ColorMean::RESIZE_DIMENSION; $i++) {
      $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'column_' . $i));
      $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'row_' . $i));
    }
    $this->assertTrue($schema->fieldExists(ColorMean::DATABASE_TABLE, 'mid'));
  }

  /**
   * @covers ::mediaSave
   * @covers ::mediaDelete
   */
  public function testMediaSaveDelete() {
    $mid = $this->mediaEntity->id();

    $this->assertNotEmpty(\Drupal::database()
      ->select(ColorMean::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)->execute()->fetchAssoc());

    $this->mediaEntity->delete();

    $this->assertEmpty(\Drupal::database()
      ->select(ColorMean::DATABASE_TABLE, 't')
      ->fields('t')
      ->condition('mid', $mid)->execute()->fetchAssoc());
  }

  /**
   * @covers ::populateTable
   */
  public function testPopulateTable() {
    $this->plugin->populateTable();
    $queue = \Drupal::database()
      ->select('queue', 'q')
      ->fields('q')
      ->condition('name', 'media_duplicate_validation')
      ->execute()
      ->fetchAssoc();
    $this->assertNotEmpty($queue);
  }

  /**
   * Test that we get similar items from two images.
   *
   * @covers ::getSimilarItems
   * @covers ::getLikeness
   * @covers ::getCloseMedia
   * @covers ::getColorData
   * @covers ::mimeType
   * @covers ::createImage
   * @covers ::resizeImage
   * @covers ::getColorValues
   * @covers ::getRowColumnAverages
   */
  public function testSimilarItems() {
    $path = 'public://smaller_logo.jpg';
    copy(__DIR__ . '/../assets/smaller_logo.jpg', $path);

    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertCount(2, $this->plugin->getSimilarItems($new_entity));
  }

  /**
   * Test that we get no similar items from two images.
   *
   * @covers ::getSimilarItems
   * @covers ::getLikeness
   * @covers ::getCloseMedia
   * @covers ::getColorData
   * @covers ::mimeType
   * @covers ::createImage
   * @covers ::resizeImage
   * @covers ::getColorValues
   * @covers ::getRowColumnAverages
   */
  public function testDifferentItems() {
    $path = 'public://different_logo.png';
    copy(__DIR__ . '/../assets/different_logo.png', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

  /**
   * Test a gif uploaded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGif() {
    $path = 'public://gif_logo.gif';
    copy(__DIR__ . '/../assets/gif_logo.gif', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

  /**
   * Test similar documents that aren't images.
   *
   * @covers ::getSimilarItems
   */
  public function testNonImage() {
    $path = 'public://testfile.txt';
    copy(__DIR__ . '/../assets/testfile.txt', $path);
    $file = File::create(['uri' => $path]);
    $file->save();
    $new_entity = Media::create([
      'bundle' => 'image',
      'field_media_image' => $file->id(),
    ]);
    $new_entity->save();

    $this->assertEmpty($this->plugin->getSimilarItems($new_entity));
  }

}
