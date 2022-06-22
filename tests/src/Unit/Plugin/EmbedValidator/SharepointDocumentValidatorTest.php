<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidator\LocalistValidator;
use Drupal\stanford_media\Plugin\EmbedValidator\SharepointDocumentValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Test the Sharepoint Document embed validator.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EmbedValidator\SharepointDocumentValidator
 */
class SharepointDocumentValidatorTest extends UnitTestCase {

  /**
   * Plugin object.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidator\SharepointDocumentValidator
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new SharepointDocumentValidator([], '', []);
  }

  /**
   * Only airtable iframe code is allowed.
   */
  public function testAllowed() {
    $this->assertFalse($this->plugin->isEmbedCodeAllowed(''));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<script src="stanford.airtable.com></script>'));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<iframe data-foo="foo" src="http://foobar.com">'));
    $this->assertTrue($this->plugin->isEmbedCodeAllowed('<div><iframe data-foo="bar" src="https://office365stanford.sharepoint.com/foo-bar"></iframe>'));
  }

  /**
   * Remove everything not necessary for the iframe.
   */
  public function testPreparedCode(){
    $this->assertEquals('', $this->plugin->prepareEmbedCode(''));
    $this->assertEquals('', $this->plugin->prepareEmbedCode('<div id="foo-bar"><script src="foo.bar"></script>'));
    $this->assertEquals('<iframe src="foo-bar"></iframe>', $this->plugin->prepareEmbedCode('<div></div><iframe src="foo-bar"><p></p></iframe><div></div>'));
  }
}
