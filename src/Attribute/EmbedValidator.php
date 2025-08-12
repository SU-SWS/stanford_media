<?php

namespace Drupal\stanford_media\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The foo_bar attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class EmbedValidator extends AttributeBase {

  /**
   * Constructs a new FooBar instance.
   *
   * @param string $id
   *   The plugin ID. There are some implementation bugs that make the plugin
   *   available only if the ID follows a specific pattern. It must be either
   *   identical to group or prefixed with the group. E.g. if the group is "foo"
   *   the ID must be either "foo" or "foo:bar".
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label,
  ) {}

}
