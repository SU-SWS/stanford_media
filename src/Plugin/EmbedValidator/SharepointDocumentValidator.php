<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_media\Attribute\EmbedValidator;
use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Sharepoint Document Iframe validation.
 */
#[EmbedValidator(
  id: 'sharepoint_document',
  label: new TranslatableMarkup('Sharepoint Document')
)]
class SharepointDocumentValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'office365stanford.sharepoint.com';

}
