<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Changes embedded video media items with vimeo provider.
 *
 * @MediaEmbedDialog(
 *   id = "vimeo_video",
 * )
 */
class VimeoVideo extends VideoEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    $source_field = static::getMediaSourceField($this->entity);
    if (parent::isApplicable()) {
      $url = $this->entity->get($source_field)->getString();
      preg_match('/^https?:\/\/(www\.)?vimeo.com\/(channels\/[a-zA-Z0-9]*\/)?(?<id>[0-9]*)(\/[a-zA-Z0-9]+)?(\#t=(\d+)s)?$/', $url, $matches);
      return isset($matches['id']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    $config = parent::getDefaultInput();
    $config += [
      'data-video-title' => 1,
      'data-video-byline' => 1,
      'data-video-color' => '',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);
    $form['video_options'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['video_options']['intro'] = [
      '#markup' => $this->t('Some videos do not support all options below.'),
    ];
    $form['video_options']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $input['data-video-autoplay'],
    ];
    $form['video_options']['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video when the video ends'),
      '#default_value' => $input['data-video-loop'],
    ];
    $form['video_options']['title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Title'),
      '#default_value' => $input['data-video-title'],
    ];
    $form['video_options']['byline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Byline'),
      '#default_value' => $input['data-video-byline'],
    ];
    $form['video_options']['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Control Color'),
      '#default_value' => $input['data-video-color'],
      '#size' => 6,
      '#max_length' => 6,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateDialogForm(array &$form, FormStateInterface $form_state) {
    parent::validateDialogForm($form, $form_state);

    $color = $form_state->getValue(['video_options', 'color']);
    if ($color && !ctype_xdigit($color)) {
      $form_state->setError($form['video_options']['color'], $this->t('Invalid Color String'));
    }
  }

}
