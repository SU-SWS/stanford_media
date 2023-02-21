<?php

namespace Drupal\stanford_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Embed validator plugin item annotation object.
 *
 * @see \Drupal\stanford_media\Plugin\EmbedValidatorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class EmbedValidator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
