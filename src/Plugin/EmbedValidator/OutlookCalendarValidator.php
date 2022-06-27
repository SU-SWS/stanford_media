<?php

namespace Drupal\stanford_media\Plugin\EmbedValidator;

use Drupal\stanford_media\Plugin\AbstractIframeValidator;

/**
 * Outlook Calendar Iframe validation.
 *
 * @EmbedValidator (
 *   id = "outlook_calendar",
 *   label = "Outlook Calendar"
 * )
 */
class OutlookCalendarValidator extends AbstractIframeValidator {

  const EMBEDDOMAIN = 'outlook.office365.com';

}
