<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Utility\Html;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;

/**
 * Base plugin class for video plugins.
 *
 * @package Drupal\stanford_media\Plugin\MediaEmbedDialog
 */
abstract class VideoEmbedBase extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [
      'autoplay' => 0,
      'loop' => 0,
      'class' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface && $this->entity->bundle() == 'video') {

    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    $field = static::getMediaSourceField($element['#media']);
    // Add the class to the container instead of the iframe.
    if (!empty($element['#display_settings']['class'])) {
      foreach (explode(' ', $element['#display_settings']['class']) as $class) {
        $element[$field][0]['#attributes']['class'][] = Html::cleanCssIdentifier($class);
      }
    }
    return $element;
  }

}
