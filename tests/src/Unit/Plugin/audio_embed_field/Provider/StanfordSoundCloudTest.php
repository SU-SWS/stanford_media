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

/**
 * Class StanfordSoundCloudTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\audio_embed_field\Provider\StanfordSoundCloud
 */
class StanfordSoundCloudTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * If the mock guzzle callback should return a response.
   *
   * @var bool
   */
  protected $guzzleResponse = TRUE;

  /**
   * If the mock cache service should return a cached value.
   *
   * @var bool
   */
  protected $returnCachedResponse = FALSE;

  /**
   * {@inheritDoc}
   */
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

  /**
   * Test the simple methods.
   */
  public function testOtherMethods() {
    $plugin = StanfordSoundCloud::create($this->container, ['input' => $this->randomMachineName()], '', []);
    $this->assertEquals('http://foo.bar/image.jpg', $plugin->getRemoteThumbnailUrl());
    $this->assertEquals('Foo Bar', $plugin->getName());

    $this->guzzleResponse = FALSE;
    $this->assertNull($plugin->getRemoteThumbnailUrl());
  }

  /**
   * Test the render array is structured correctly.
   */
  public function testRenderEmbedCode() {
    $plugin = StanfordSoundCloud::create($this->container, ['input' => $this->randomMachineName()], '', []);
    $render_array = $plugin->renderEmbedCode(100, 100, FALSE);

    $this->assertEquals('audio_embed_iframe', $render_array['#type']);
    $this->assertEquals('https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/123456789', $render_array['#url']);
    $this->assertArraySubset([
      'width' => 100,
      'height' => 100,
      'frameborder' => 0,
    ], $render_array['#attributes']);
  }

  /**
   * Mock guzzle client response callback.
   */
  public function guzzleRequestCallback() {
    $response = $this->createMock(ResponseInterface::class);
    if ($this->guzzleResponse === TRUE) {
      $response->method('getBody')
        ->willReturn(json_encode($this->getVideoData()));
    }
    if (is_null($this->guzzleResponse)) {
      $request = $this->createMock(RequestInterface::class);
      $response = $this->createMock(ResponseInterface::class);
      throw new ClientException('It broke.', $request, $response);
    }
    if ($this->guzzleResponse === FALSE) {
      $response->method('getBody')->willReturn(json_encode([]));
    }
    return $response;
  }

  /**
   * Mock cache service callback.
   */
  public function getCacheCallback() {
    if ($this->returnCachedResponse) {
      $cache_item = new \stdClass();
      $cache_item->data = $this->getVideoData();
      return $cache_item;
    }
  }

  /**
   * Get the SoundCloud api response data.
   */
  protected function getVideoData() {
    return [
      'html' => '<iframe src="https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Fplaylists%2F123456789&show_artwork=true"></iframe>',
      'thumbnail_url' => 'http://foo.bar/image.jpg',
      'title' => 'Foo Bar',
    ];
  }

}
