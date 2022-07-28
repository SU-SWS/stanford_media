<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidatorBase;

/**
 * Twitter validation.
 *
 * @EmbedValidator (
 *   id = "twitter",
 *   label = "Twitter"
 * )
 */
class TwitterValidator extends EmbedValidatorBase {

  /**
   * {@inheritDoc}
   */
  public function isEmbedCodeAllowed(string $code): bool {
    $code = self::prepareEmbedCode($code);
    return !empty($code);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    // Strip out a bunch of stuff we don't care about.
    $code = preg_replace("/\r\n|\r|\n/", '', strip_tags($code, '<blockquote> <script> <a> <p>'));

    // The twitter widget script keys off of the class name with twitter- for
    // the prefix. Like twitter-tweet, twitter-timline, etc. We need to grab
    // the element that has that class and then we will retain everything within
    // that element.
    preg_match('/<(\w+) .*?class="twitter-(.*?)".*?>/', $code, $twitter_container);

    // There should only be 1 <script> tag with the twitter domain.
    preg_match('/<script.*?src="(.*platform\.twitter\.com.*?)".*?>/', $code, $script_matches);
    if (!isset($twitter_container[1])  || !isset($script_matches[0])) {
      return '';
    }

    // Grab the twitter container that we found earlier.
    preg_match('/<' . $twitter_container[1] . '.*?\/' . $twitter_container[1] . '>/', $code, $container);

    // Combine the container with the script element.
    return reset($container) . reset($script_matches) . '</script>';
  }

}
