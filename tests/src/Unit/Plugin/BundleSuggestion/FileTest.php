<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\BundleSuggestion;

use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Plugin\BundleSuggestion\File;

/**
 * Class FileTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\BundleSuggestion\File
 */
class FileTest extends BundleSuggestionTestBase {

  /**
   * Test the plugins methods.
   */
  public function testPlugin() {
    $plugin = File::create($this->container, [], '', []);
    $this->assertNull($plugin->getBundleFromString($this->randomMachineName()));

    $this->assertNull($plugin->getBundleFromString('public://' . $this->randomMachineName()));
    $this->assertInstanceOf(MediaTypeInterface::class, $plugin->getBundleFromString('public://' . $this->randomMachineName() . '.foo'));
  }

  /**
   * {@inheritDoc}
   */
  public function fieldGetTypeCallback() {
    return '';
  }

}
