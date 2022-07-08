<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Smartsheet Iframe validation.
 *
 * @EmbedValidator (
 *   id = "smartsheet",
 *   label = "Smartsheet"
 * )
 */
class SmartsheetValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'app.smartsheet.com';

}
