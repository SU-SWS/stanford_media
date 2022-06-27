<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\AbstractIframeValidator;

/**
 * Google Calendar Iframe validation.
 *
 * @EmbedValidator (
 *   id = "google_iframe",
 *   label = "Google IFrames"
 * )
 */
class GoogleIframeValidator extends AbstractIframeValidator {

  const EMBEDDOMAIN = '.google.com';

}
