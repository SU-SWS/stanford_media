<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Sharepoint Document Iframe validation.
 *
 * @EmbedValidator (
 *   id = "sharepoint_document",
 *   label = "Sharepoint Document"
 * )
 */
class SharepointDocumentValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'office365stanford.sharepoint.com';

}
