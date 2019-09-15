<?php

namespace Drupal\stanford_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser display annotation object.
 *
 * @see hook_entity_browser_display_info_alter()
 *
 * @Annotation
 */
class MediaEmbedDialog extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
