<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Airtable Iframe validation.
 *
 * @EmbedValidator (
 *   id = "airtable",
 *   label = "Airtable"
 * )
 */
class AirtableValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'airtable.com';

}
