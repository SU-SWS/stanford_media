<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\AbstractIframeValidator;

/**
 * Sharepoint Document Iframe validation.
 *
 * @EmbedValidator (
 *   id = "sharepoint_document",
 *   label = "Sharepoint Document"
 * )
 */
class SharepointDocumentValidator extends AbstractIframeValidator {

  const EMBEDDOMAIN = 'office365stanford.sharepoint.com';

}
