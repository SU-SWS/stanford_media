<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_media\Attribute\EmbedValidator;
use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Smartsheet Iframe validation.
 */
#[EmbedValidator(
  id: 'smartsheet',
  label: new TranslatableMarkup('Smartsheet')
)]
class SmartsheetValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'app.smartsheet.com';

}
