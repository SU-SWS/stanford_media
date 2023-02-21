<?php

namespace Drupal\stanford_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a bundle suggestion object.
 *
 * We need this so that our docblock annotation is unique.
 *
 * @see hook_entity_browser_display_info_alter()
 *
 * @Annotation
 */
class BundleSuggestion extends Plugin {

  /**
   * Field type names.
   *
   * @var string[]
   */
  protected $field_types = [];

}
