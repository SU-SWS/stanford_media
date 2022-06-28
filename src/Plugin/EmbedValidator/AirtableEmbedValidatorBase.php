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
class AirtableEmbedValidatorBase extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'airtable.com';

}
