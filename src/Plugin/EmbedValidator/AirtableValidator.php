<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_media\Attribute\EmbedValidator;
use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Airtable Iframe validation.
 */
#[EmbedValidator(
  id: 'airtable',
  label: new TranslatableMarkup('Airtable')
)]
class AirtableValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'airtable.com';

}
