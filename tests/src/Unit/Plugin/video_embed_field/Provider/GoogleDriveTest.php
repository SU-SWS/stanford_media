<?php

namespace Drupal\Tests\stanford_media\Plugin\video_embed_field\Provider;

use Drupal\stanford_media\Plugin\video_embed_field\Provider\GoogleDrive;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Class GoogleDriveTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\video_embed_field\Provider\GoogleDrive
 */
class GoogleDriveTest extends UnitTestCase {

  /**
   * Google drive plugin.
   *
   * @var GoogleDrive
   */
  protected $plugin;

  /**
   * Randomly generated video id.
   *
   * @var string
   */
  protected $videoId;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $id = $this->randomMachineName(33);
    $configuration = [
      'input' => "https://drive.google.com/file/d/$id/view",
    ];

    $this->videoId = $id;
    $plugin_definition = [];
    $http_client = $this->createMock(ClientInterface::class);
    $this->plugin = new GoogleDrive($configuration, 'google_drive', $plugin_definition, $http_client);
  }

  /**
   * Test the plugin grabs the correct value from the url.
   */
  public function testGetId() {
    $input = 'http://wwww.domain.tld';
    $this->assertFalse(GoogleDrive::getIdFromInput($input));

    $id = $this->randomMachineName(33);
    $input = "https://drive.google.com/file/d/$id/view";
    $this->assertEquals($id, GoogleDrive::getIdFromInput($input));

  }

  /**
   * Check the render method array.
   */
  public function testRenderMethod() {
    $render_array = $this->plugin->renderEmbedCode(500, 500, FALSE);
    $this->assertEquals("https://drive.google.com/file/d/{$this->videoId}/preview", $render_array['#url']);
  }

}

