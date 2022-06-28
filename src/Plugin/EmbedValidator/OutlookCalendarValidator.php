<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\IframeEmbedValidatorBase;

/**
 * Outlook Calendar Iframe validation.
 *
 * @EmbedValidator (
 *   id = "outlook_calendar",
 *   label = "Outlook Calendar"
 * )
 */
class OutlookCalendarValidator extends IframeEmbedValidatorBase {

  const EMBED_DOMAIN = 'outlook.office365.com';

}
