<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Changes embedded video media items with youtube provider.
 *
 * @MediaEmbedDialog(
 *   id = "youtube_video",
 *   media_type = "video",
 *   video_provider = "youtube"
 * )
 */
class YoutubeVideo extends VideoEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    $config = parent::getDefaultInput();
    $config += [
      'start' => 0,
      'rel' => 0,
      'showinfo' => 1,
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
    $form['video_options']['start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start at'),
      '#description' => $this->t('Enter a time in the format mm:ss'),
      '#size' => 5,
      '#default_value' => $this->getReadableTime($input['start']),
    ];

    $form['video_options']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $input['autoplay'],
    ];

    $form['video_options']['rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show suggested videos when the video finishes'),
      '#default_value' => $input['rel'],
    ];

    $form['video_options']['showinfo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show video title and player actions'),
      '#default_value' => $input['showinfo'],
    ];

    $form['video_options']['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video when the video ends'),
      '#default_value' => $input['loop'],
    ];

    $form['video_options']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video Class'),
      '#description' => $this->t('Optionally provide classes that will be added to the video container.'),
      '#default_value' => $input['class'],
      '#size' => 25,
      '#maxlength' => 128,
    ];
  }

  /**
   * Convert seconds into minutes:seconds.
   *
   * @param int $seconds
   *   Number of seconds to convert into readable time.
   *
   * @return string
   *   Formatted time.
   */
  protected function getReadableTime($seconds) {
    $mins = floor($seconds / 60 % 60);
    $secs = floor($seconds % 60);
    return sprintf('%02d:%02d', $mins, $secs);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDialogForm(array &$form, FormStateInterface $form_state) {
    parent::validateDialogForm($form, $form_state);
    $start = $form_state->getValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ]);

    if (is_numeric($start)) {
      return;
    }

    // Check if start at is in the correct time format.
    if (!(preg_match('/^\d{1}:\d{2}$/', $start) || preg_match('/^\d{2}:\d{2}$/', $start))) {
      $form_state->setError($form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['start'], $this->t('Invalid Time Entry'));
      return;
    }

    $seconds = 0;
    sscanf($start, "%d:%d", $minutes, $seconds);
    $start = $minutes * 60 + $seconds;

    $form_state->setValue([
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'start',
    ], $start);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    if (!empty($element['#display_settings'])) {
      $field = static::getMediaSourceField($element['#media']);
      foreach ($element['#display_settings'] as $key => $value) {
        if ($key == 'class' || empty($value)) {
          continue;
        }
        $element[$field][0]['children']['#query'][$key] = $value;
      }
    }
    return parent::preRender($element);
  }

}
