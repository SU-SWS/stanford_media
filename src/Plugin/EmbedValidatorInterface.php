<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Oembed validator plugin plugins.
 */
interface EmbedValidatorInterface extends PluginInspectionInterface {

  /**
   * Check if the given html code is allowed.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return bool
   *   If the given embeddable code is allowed.
   */
  public function isEmbedCodeAllowed(string $code): bool;

  /**
   * Prepare and process the raw html code.
   *
   * @param string $code
   *   Raw html embed code.
   *
   * @return string
   *   Prepared and ready html code.
   */
  public function prepareEmbedCode(string $code): string;

}
