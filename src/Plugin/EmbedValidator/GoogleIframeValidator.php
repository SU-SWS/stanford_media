<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidatorBase;

/**
 * Google Calendar Iframe validation.
 *
 * @EmbedValidator (
 *   id = "google_iframe",
 *   label = "Google IFrames"
 * )
 */
class GoogleIframeValidator extends EmbedValidatorBase {

  /**
   * {@inheritDoc}
   */
  public function isEmbedCodeAllowed(string $code): bool {
    $code = str_replace("\n", ' ', $code);
    preg_match('/<iframe.* src="(.+?)"/', $code, $matches);
    if (empty($matches[1])) {
      return FALSE;
    }
    $source = parse_url($matches[1]);
    return strpos($source['host'], '.google.com') !== FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    preg_match('/<iframe.*?>/', $code, $modified_code);
    return $modified_code ? $modified_code[0] . '</iframe>' : '';
  }

}
