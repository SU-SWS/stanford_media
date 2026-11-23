<?php

namespace Drupal\stanford_media\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;

/**
 * Defines a bundle suggestion plugin attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class BundleSuggestion extends AttributeBase {

  /**
   * Constructs a new BundleSuggestion instance.
   *
   * @param string $id
   *   The plugin ID. There are some implementation bugs that make the plugin
   *   available only if the ID follows a specific pattern. It must be either
   *   identical to group or prefixed with the group. E.g. if the group is "foo"
   *   the ID must be either "foo" or "foo:bar".
   * @param array $field_types
   *   The source field types this plugin suggests bundles for.
   */
  public function __construct(
    public readonly string $id,
    public readonly array $field_types,
  ) {}

}
