<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
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
      'data-video-autoplay' => 0,
      'data-video-loop' => 0,
      'data-video-class' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    $source_field = $this->entity->getFieldDefinition(self::getMediaSourceField($this->entity));
    if ($this->entity instanceof MediaInterface && $source_field->getType() == 'video_embed_field') {
      return TRUE;
    }
    return FALSE;
  }

    /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('video_options') as $key => $value) {
      $values['attributes']["data-video-$key"] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity) {
    parent::embedAlter($build, $entity);
    $source_field = static::getMediaSourceField($entity);

    foreach ($build['#attributes'] as $key => $value) {
      if (empty($value) || strpos($key, 'data-video-') === FALSE) {
        continue;
      }

      // Add the class to the container instead of the iframe.
      if ($key == 'data-video-class') {
        foreach (explode(' ', $build['#attributes']['class']) as $class) {
          $build[$source_field][0]['#attributes']['class'][] = Html::cleanCssIdentifier($class);
        }
        continue;
      }
      $build[$source_field][0]['children']['#query'][str_replace('data-video-', '', $key)] = $value;
    }
  }

}
