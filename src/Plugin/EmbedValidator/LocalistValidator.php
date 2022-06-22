<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\EmbedValidatorBase;

/**
 * Localist Events embed code validation.
 *
 * @EmbedValidator (
 *   id = "localist",
 *   label = "Localist Events"
 * )
 */
class LocalistValidator extends EmbedValidatorBase {

  /**
   * {@inheritDoc}
   */
  public function isEmbedCodeAllowed(string $code): bool {
    $code = str_replace("\n", ' ', $code);
    preg_match('/id="localist-widget-/', $code, $localist_id);
    preg_match('/<script.*src=".*stanford.*localist.*?"/', $code, $localist_script);
    return !empty($localist_id) && !empty($localist_script);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode(string $code): string {
    $code = str_replace("\n", ' ', $code);
    // Localist only needs the div with the widget id and the javascript. Ignore
    // any other tags or contents.
    preg_match('/<div id="localist-widget.*?\/div>/s', $code, $modified_code);
    preg_match('/<script .*?src=".*localist.*<\/script>/s', $code, $script);
    $modified_code = array_merge($modified_code, $script);
    return implode("\n", $modified_code);
  }

}
