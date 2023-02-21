<?php

namespace Drupal\stanford_media\Plugin;

/**
 * Abstract IFrame Validator.
 */
abstract class IframeEmbedValidatorBase extends EmbedValidatorBase {

  /**
   * Domain string used to compare the iframe src attribute.
   */
  const EMBED_DOMAIN = '';

  /**
   * {@inheritDoc}
   */
  public function isEmbedCodeAllowed(string $code): bool {
    $iframe_code = $this->prepareEmbedCode($code);

    preg_match('/src="(.*?)"/', $iframe_code, $source_matches);
    preg_match('/title="(.*?)"/', $iframe_code, $title_matches);

    if (empty($source_matches[1]) || empty($title_matches[1])) {
      return FALSE;
    }
    $source = parse_url($source_matches[1]);
    return isset($source['host']) && str_contains($source['host'], $this::EMBED_DOMAIN);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    $code = str_replace(["\r", "\n"], "", $code);
    preg_match('/<iframe.*?>/', $code, $modified_code);
    return $modified_code ? $modified_code[0] . '</iframe>' : '';
  }

}
