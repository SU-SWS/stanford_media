<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\stanford_media\Plugin\EmbedValidatorBase;

/**
 * Abstract IFrame Validator.
 *
 */
abstract class AbstractIframeValidator extends EmbedValidatorBase {

  /**
   * {@inheritDoc}
   */
  public function isEmbedCodeAllowed(string $code): bool {
    $code = str_replace("\n", ' ', $code);
    preg_match('/<iframe.* src="(.+?)"/', $code, $source_matches);
    preg_match('/<iframe.* title="(.+?)"/', $code, $title_matches);
    if (empty($source_matches[1]) || empty($title_matches[1])) {
      return FALSE;
    }
    $source = parse_url($source_matches[1]);
    return $source['host'] == self::EMBEDDOMAIN;
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    preg_match('/<iframe.*?>/', $code, $modified_code);
    return $modified_code ? $modified_code[0] . '</iframe>' : '';
  }

}
