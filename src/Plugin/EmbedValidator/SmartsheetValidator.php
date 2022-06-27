<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\AbstractIframeValidator;

/**
 * Smartsheet Iframe validation.
 *
 * @EmbedValidator (
 *   id = "smartsheet",
 *   label = "Smartsheet"
 * )
 */
class SmartsheetValidator extends AbstractIframeValidator {

  const EMBEDDOMAIN = 'app.smartsheet.com';

}
