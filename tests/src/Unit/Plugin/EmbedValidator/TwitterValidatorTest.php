<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidator\TwitterValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Test the Twitter embed validator.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EmbedValidator\TwitterValidator
 */
class TwitterValidatorTest extends UnitTestCase {

  /**
   * Plugin object.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidator\TwitterValidator
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->plugin = new TwitterValidator([], '', []);
  }

  /**
   * Only airtable iframe code is allowed.
   */
  public function testAllowed() {
    $link = '<a class="twitter-timeline"></a>';
    $script = '<script src="//platform.twitter.com/widgets.js"></script>';
    $this->assertFalse($this->plugin->isEmbedCodeAllowed(''));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed($script));
    $this->assertFalse($this->plugin->isEmbedCodeAllowed($link));
    $this->assertTrue($this->plugin->isEmbedCodeAllowed($link . $script));

    $sample_code = '<blockquote class="twitter-tweet">
Felis erat varius dolor proin eu orci bibendum <a href="https://twitter.com/hashtag/sample">Suspendisse ac tristique</a>
  <a href="https://twitter.com/sample/status/123456789123456">July 25, 2022</a>
</blockquote>
 <script async src="https://platform.twitter.com/widgets.js" charset="utf-8">
</script>';
    $this->assertTrue($this->plugin->isEmbedCodeAllowed($sample_code));
  }

  /**
   * Remove everything not necessary for the iframe.
   */
  public function testPreparedCode(){
    $expected  = '<a class="twitter-timeline">Follow this twitter feed.</a><script src="//platform.twitter.com/widgets.js"></script>';

    $this->assertEquals('', $this->plugin->prepareEmbedCode(''));
    $this->assertEquals('', $this->plugin->prepareEmbedCode('<div id="foo-bar"><script src="foo.bar"></script>'));
    $this->assertEquals($expected, $this->plugin->prepareEmbedCode('<p><a class="twitter-timeline">Follow this twitter feed.</a><div>some garbage</div><script src="//platform.twitter.com/widgets.js"></script></p><script src="http://foobar.com">should be removed</script>'));
  }

}
