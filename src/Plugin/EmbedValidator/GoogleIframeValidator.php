<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Google Calendar Iframe validation.
 *
 * @EmbedValidator (
 *   id = "google_iframe",
 *   label = "Google IFrames"
 * )
 */
class GoogleIframeValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = '.google.com';

}
