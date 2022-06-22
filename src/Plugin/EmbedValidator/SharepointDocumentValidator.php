<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidatorBase;

/**
 * Sharepoint Document Iframe validation.
 *
 * @EmbedValidator (
 *   id = "sharepoint_document",
 *   label = "Sharepoint Document"
 * )
 */
class SharepointDocumentValidator extends EmbedValidatorBase {

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
    return $source['host'] == 'office365stanford.sharepoint.com';
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    preg_match('/<iframe.*?>/', $code, $modified_code);
    return $modified_code ? $modified_code[0] . '</iframe>' : '';
  }

}
