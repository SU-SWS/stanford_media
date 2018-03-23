<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\MediaEmbedDialogBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video_embed_field\ProviderManager;

/**
 * Changes embedded video media items with youtube provider.
 *
 * @MediaEmbedDialog(
 *   id = "youtube_video",
 *   media_type = "video"
 * )
 */
class YoutubeVideo extends MediaEmbedDialogBase {

  /**
   * Video manager to validate the url matches an available provider.
   *
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $videoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('video_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, ProviderManager $video_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->videoManager = $video_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [
      'start' => 0,
      'autoplay' => 0,
      'rel' => 0,
      'showinfo' => 1,
      'loop' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface && $this->entity->bundle() == 'video') {

      $source_field = static::getMediaSourceField($this->entity);
      $url = $this->entity->get($source_field)->getValue()[0]['value'];
      $provider = $this->videoManager->loadProviderFromInput($url);

      if ($provider->getPluginId() == 'youtube') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);
    unset($form['attributes']['data-align']);

    $form['attributes'][$this->settingsKey]['start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start at'),
      '#description' => $this->t('Enter a time in the format mm:ss'),
      '#size' => 5,
      '#default_value' => $this->getReadableTime($input['start']),
    ];

    $form['attributes'][$this->settingsKey]['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $input['autoplay'],
    ];

    $form['attributes'][$this->settingsKey]['rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show suggested videos when the video finishes'),
      '#default_value' => $input['rel'],
    ];

    $form['attributes'][$this->settingsKey]['showinfo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show video title and player actions'),
      '#default_value' => $input['showinfo'],
    ];

    $form['attributes'][$this->settingsKey]['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video when the video ends'),
      '#default_value' => $input['loop'],
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
  public static function validateDialogForm(array &$form, FormStateInterface $form_state) {
    parent::validateDialogForm($form, $form_state);
    $start = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
      'start',
    ]);

    if (is_numeric($start)) {
      return;
    }

    // Check if start at is in the correct time format.
    if (!(preg_match('/^\d{1}:\d{2}$/', $start) || preg_match('/^\d{2}:\d{2}$/', $start))) {
      $form_state->setError($form['attributes']['data-entity-embed-display-settings']['start'], t('Invalid Time Entry'));
      return;
    }

    $seconds = 0;
    sscanf($start, "%d:%d", $minutes, $seconds);
    $start = $minutes * 60 + $seconds;

    $form_state->setValue([
      'attributes',
      'data-entity-embed-display-settings',
      'start',
    ], $start);

  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element) {
    if (!empty($element['#display_settings'])) {
      $field = static::getMediaSourceField($element['#media']);
      foreach ($element['#display_settings'] as $key => $value) {
        $element[$field][0]['children']['#query'][$key] = $value;
      }
    }
    return $element;
  }

}
