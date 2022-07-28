<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidator\LocalistValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Test the localist embed validator.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EmbedValidator\LocalistValidator
 */
class LocalistValidatorTest extends UnitTestCase {

  /**
   * Plugin object.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidator\LocalistValidator
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setup(): void {
    parent::setUp();
    $this->plugin = new LocalistValidator([], '', []);
  }

  /**
   * Only localist code is allowed.
   */
  public function testAllowed() {
    $this->assertFalse($this->plugin->isEmbedCodeAllowed(''));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<script src="stanford.enterprise.localist.com"></script>'));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<div id="localist-widget-1234"><script src="foo.bar"></script>'));
    $this->assertTrue($this->plugin->isEmbedCodeAllowed('<div id="localist-widget-1234"></div><script src="stanford.enterprise.localist.com"></script>'));
  }

  /**
   * Remove everything not necessary for localist.
   */
  public function testPreparedCode(){
    $this->assertEquals('', $this->plugin->prepareEmbedCode(''));
    $this->assertEquals('', $this->plugin->prepareEmbedCode('<div id="foo-bar"><script src="foo.bar"></script>'));

    $div = '<div id="localist-widget-1234"></div>';
    $script = '<script src="stanford.enterprise.localist.com"></script>';

    $this->assertEquals("$div\n$script", $this->plugin->prepareEmbedCode("$div$script"));
    $this->assertEquals("$div\n$script", $this->plugin->prepareEmbedCode("$div$script<a href='foo/bar'>foobar</a>"));
    $this->assertEquals("$div\n$script", $this->plugin->prepareEmbedCode("<div>$div$script</div>"));
  }

}
