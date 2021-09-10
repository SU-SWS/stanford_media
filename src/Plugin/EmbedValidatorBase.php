<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Oembed validator plugin plugins.
 */
abstract class EmbedValidatorBase extends PluginBase implements EmbedValidatorInterface {

  /**
   * {@inheritDoc}
   */
  public function prepareEmbedCode($code): string {
    return $code;
  }

}
