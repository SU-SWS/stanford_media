<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogManager;
use Drupal\Tests\UnitTestCase;

/**
 * Class MediaEmbedDialogManagerTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\MediaEmbedDialogManager
 */
class MediaEmbedDialogManagerTest extends UnitTestCase {

  public function testManager(){
    $namespaces  = $this->createMock(\Traversable::class);
    $cache_backend = $this->createMock(CacheBackendInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);

    $manager = new MediaEmbedDialogManager($namespaces,  $cache_backend,  $module_handler);
    $this->assertInstanceOf(MediaEmbedDialogManager::class, $manager);
  }

}
