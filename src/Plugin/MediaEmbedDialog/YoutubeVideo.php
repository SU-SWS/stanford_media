<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Changes embedded video media items with youtube provider.
 *
 * @MediaEmbedDialog(
 *   id = "youtube_video",
 * )
 */
class YoutubeVideo extends VideoEmbedBase {

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
      'data-video-start' => 0,
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
      '#default_value' => $this->getReadableTime($input['data-video-start']),
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

    $form['video_options']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video Class'),
      '#description' => $this->t('Optionally provide classes that will be added to the video container.'),
      '#default_value' => $input['data-video-class'],
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
    $start = $form_state->getValue(['video_options', 'start']);

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

    $form_state->setValue(['video_options', 'start'], $start);
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity) {
    parent::embedAlter($build, $entity);

    $field = static::getMediaSourceField($entity);
    foreach ($build['#attributes'] as $key => $value) {
      if ($key == 'class' || empty($value) || strpos($key, 'data-video-') === FALSE) {
        continue;
      }
      $build[$field][0]['children']['#query'][str_replace('data-video-', '', $key)] = $value;
    }
  }

}
