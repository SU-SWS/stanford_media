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

  /**
   * The machine name of the media type to alter.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $media_type;

}
