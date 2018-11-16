<?php

namespace Drupal\Tests\media_duplicate_validation\Kernel\Plugin\QueueWorker;

use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation\Md5;
use Drupal\Tests\media_duplicate_validation\Kernel\Plugin\MediaDuplicateValidationTestBase;

/**
 * Queue worker test class.
 *
 * @coversDefaultClass \Drupal\media_duplicate_validation\Plugin\QueueWorker\CronMediaValidationPopulate
 */
class CronMediaValidationPopulateTest extends MediaDuplicateValidationTestBase {

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::processItem
   */
  public function testQueueWorker() {
    \Drupal::database()->schema()->dropTable(Md5::DATABASE_TABLE);
    $this->duplicationManager->buildPluginSchemas();

    $this->assertEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->execute()
      ->fetchAssoc());
    /** @var \Drupal\Core\Queue\QueueWorkerManager $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = $queue_manager->createInstance('media_duplicate_validation');
    $queue_item = \Drupal::queue('media_duplicate_validation')->claimItem();
    $queue_worker->processItem($queue_item->data);
    \Drupal::queue('media_duplicate_validation')->deleteItem($queue_item);
    $this->assertNotEmpty(\Drupal::database()
      ->select(Md5::DATABASE_TABLE, 't')
      ->fields('t')
      ->execute()
      ->fetchAssoc());
  }

}
