<?php

namespace Drupal\stanford_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a media embed dialog object.
 *
 * @see hook_media_embed_dialog_info_alter()
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
