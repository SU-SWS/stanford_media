<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\BundleSuggestion;

use Drupal\stanford_media\Plugin\BundleSuggestion\VideoEmbed;
use Drupal\video_embed_field\ProviderManager;
use Drupal\video_embed_field\ProviderPluginInterface;

/**
 * Class VideoEmbedTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\BundleSuggestion\VideoEmbed
 */
class VideoEmbedTest extends BundleSuggestionTestBase {

  protected $validFieldType = TRUE;

  /**
   * When the video is valid, the plugin should return a bundle.
   */
  public function testValidVideoEmbedPlugin() {
    $provider = $this->createMock(ProviderPluginInterface::class);
    $provider->method('getName')->willReturn('Bar');

    $video_provider_manager = $this->createMock(ProviderManager::class);
    $video_provider_manager->method('loadProviderFromInput')
      ->willReturn($provider);

    $this->container->set('video_embed_field.provider_manager', $video_provider_manager);

    $plugin = VideoEmbed::create($this->container, [], '', []);
    $this->assertNotEmpty($plugin->getBundleFromString($this->randomMachineName()));
    $this->assertEquals('Bar', $plugin->getName('foo'));

    $this->validFieldType = FALSE;
    $this->assertNull($plugin->getBundleFromString($this->randomMachineName()));
  }

  /**
   * When the video is invalid, the plugin should return NULL.
   */
  public function testEmptyVideoEmbedPlugin() {
    $video_provider_manager = $this->createMock(ProviderManager::class);
    $video_provider_manager->method('loadProviderFromInput')
      ->willReturn(NULL);

    $this->container->set('video_embed_field.provider_manager', $video_provider_manager);

    $plugin = VideoEmbed::create($this->container, [], '', []);
    $this->assertNull($plugin->getBundleFromString($this->randomMachineName()));
  }

  /**
   * {@inheritDoc}}
   */
  public function fieldGetTypeCallback() {
    return $this->validFieldType ? 'video_embed_field' : $this->randomMachineName();
  }

}
