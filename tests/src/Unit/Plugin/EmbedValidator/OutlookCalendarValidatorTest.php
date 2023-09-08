<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidator\LocalistValidator;
use Drupal\stanford_media\Plugin\EmbedValidator\OutlookCalendarValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Test the Outlook Calendar embed validator.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EmbedValidator\OutlookCalendarValidator
 */
class OutlookCalendarValidatorTest extends UnitTestCase {

  /**
   * Plugin object.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidator\OutlookCalendarValidator
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->plugin = new OutlookCalendarValidator([], '', []);
  }
  /**
   * Only airtable iframe code is allowed.
   */
  public function testAllowed() {
    $this->assertFalse($this->plugin->isEmbedCodeAllowed(''));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<script src="stanford.airtable.com"></script>'));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed('<iframe data-foo="foo" src="http://foobar.com">'));
    $this->assertTrue($this->plugin->isEmbedCodeAllowed('<div><iframe data-foo="bar" src="https://outlook.office365.com/foo-bar" title="test embed"></iframe>'));
  }

  /**
   * Remove everything not necessary for the iframe.
   */
  public function testPreparedCode(){
    $this->assertEquals('', $this->plugin->prepareEmbedCode(''));
    $this->assertEquals('', $this->plugin->prepareEmbedCode('<div id="foo-bar"><script src="foo.bar"></script>'));
    $this->assertEquals('<iframe src="foo-bar" title="test embed"></iframe>', $this->plugin->prepareEmbedCode('<div></div><iframe src="foo-bar" title="test embed"><p></p></iframe><div></div>'));
  }

}
