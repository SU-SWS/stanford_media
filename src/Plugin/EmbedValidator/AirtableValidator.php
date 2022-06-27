<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\AbstractIframeValidator;

/**
 * Airtable Iframe validation.
 *
 * @EmbedValidator (
 *   id = "airtable",
 *   label = "Airtable"
 * )
 */
class AirtableValidator extends AbstractIframeValidator {

  const EMBEDDOMAIN = 'airtable.com';

}
