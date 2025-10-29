<?php

namespace Drupal\stanford_media\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;

/**
 * The Media Embed Dialog attribute.
 *
 * @codeCoverageIgnore
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class MediaEmbedDialog extends AttributeBase {

  /**
   * Constructs a new Dialog Plugin instance.
   *
   * @param string $id
   *   The plugin ID.
   */
  public function __construct(public readonly string $id) {}

}
