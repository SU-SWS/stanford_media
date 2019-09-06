<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\BundleSuggestion;

use Drupal\audio_embed_field\ProviderManager;
use Drupal\stanford_media\Plugin\BundleSuggestion\AudioEmbed;

/**
 * Class AudioEmbedTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\BundleSuggestion\AudioEmbed
 */
class AudioEmbedTest extends BundleSuggestionTestBase {

  /**
   * When the audio is valid, the plugin should return a bundle.
   */
  public function testValidAudioEmbedPlugin() {
    $audio_provider_manager = $this->createMock(ProviderManager::class);
    $audio_provider_manager->method('loadDefinitionFromInput')
      ->willReturn(['id' => 'foo']);

    $this->container->set('audio_embed_field.provider_manager', $audio_provider_manager);

    $plugin = AudioEmbed::create($this->container, [], '', []);
    $this->assertNotEmpty($plugin->getBundleFromString($this->randomMachineName()));
    $this->assertNull($plugin->getName($this->randomMachineName()));
  }

  /**
   * When the audio is invalid, the plugin should return NULL.
   */
  public function testEmptyAudioEmbedPlugin() {
    $audio_provider_manager = $this->createMock(ProviderManager::class);
    $audio_provider_manager->method('loadDefinitionFromInput')
      ->willReturn(NULL);

    $this->container->set('audio_embed_field.provider_manager', $audio_provider_manager);

    $plugin = AudioEmbed::create($this->container, [], '', []);
    $this->assertNull($plugin->getBundleFromString($this->randomMachineName()));
  }

  /**
   * {@inheritDoc}}
   */
  public function fieldGetTypeCallback() {
    return 'audio_embed_field';
  }

}
