<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\audio_embed_field\Provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\stanford_media\Plugin\audio_embed_field\Provider\StanfordSoundCloud;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StanfordSoundCloudTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  protected $guzzleResponse = TRUE;

  protected $returnCachedResponse = FALSE;

  protected function setUp() {
    parent::setUp();
    $guzzle = $this->createMock(ClientInterface::class);
    $guzzle->method('request')
      ->will($this->returnCallback([$this, 'guzzleRequestCallback']));

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')
      ->will($this->returnCallback([$this, 'getCacheCallback',]));

    $this->container = new ContainerBuilder();
    $this->container->set('http_client', $guzzle);
    $this->container->set('cache.default', $cache);

    \Drupal::setContainer($this->container);
  }

  /**
   * Test Applicable plugin.
   */
  public function testSoundCloud() {
    $this->assertFalse(StanfordSoundCloud::isApplicable(''));

    $this->guzzleResponse = FALSE;
    $this->assertFalse(StanfordSoundCloud::isApplicable($this->randomMachineName()));

    $this->guzzleResponse = NULL;
    $this->assertFalse(StanfordSoundCloud::isApplicable($this->randomMachineName()));

    $this->guzzleResponse = TRUE;
    $this->assertTrue(StanfordSoundCloud::isApplicable($this->randomMachineName()));

    $this->returnCachedResponse = TRUE;
    $this->assertTrue(StanfordSoundCloud::isApplicable($this->randomMachineName()));

    $plugin = StanfordSoundCloud::create($this->container, ['input' => $this->randomMachineName()], '', []);
    $this->assertInstanceOf(StanfordSoundCloud::class, $plugin);
  }

  public function guzzleRequestCallback() {
    $response = $this->createMock(ResponseInterface::class);
    if ($this->guzzleResponse === TRUE) {
      $body = [
        'html' => '<iframe src="https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F647171277&show_artwork=true\"></iframe>',
      ];
      $response->method('getBody')->willReturn(json_encode($body));
    }
    if (is_null($this->guzzleResponse)) {
      $request = $this->createMock(RequestInterface::class);
      $response = $this->createMock(ResponseInterface::class);
      throw new ClientException('It broke.', $request, $response);
    }
    if ($this->guzzleResponse === FALSE) {
      $body = [];
      $response->method('getBody')->willReturn(json_encode($body));
    }
    return $response;
  }

  public function getCacheCallback() {
    if ($this->returnCachedResponse) {
      $cache_item = new \stdClass();
      $cache_item->data = [
        'html' => '<iframe src="https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F647171277&show_artwork=true\"></iframe>',
      ];
      return $cache_item;
    }
  }

}
