<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Changes embedded video media items with vimeo provider.
 *
 * @MediaEmbedDialog(
 *   id = "vimeo_video",
 *   media_type = "video",
 *   video_provider = "vimeo"
 * )
 */
class VimeoVideo extends VideoEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    $config = parent::getDefaultInput();
    $config += [
      'title' => 1,
      'byline' => 1,
      'color' => '',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);
    unset($form['attributes']['data-align']);

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['intro'] = [
      '#markup' => $this->t('Some videos do not support all options below.'),
    ];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $input['autoplay'],
    ];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video when the video ends'),
      '#default_value' => $input['loop'],
    ];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Title'),
      '#default_value' => $input['title'],
    ];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['byline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Byline'),
      '#default_value' => $input['byline'],
    ];
    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Control Color'),
      '#default_value' => $input['color'],
      '#size' => 6,
      '#max_length' => 6,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDialogForm(array &$form, FormStateInterface $form_state) {
    parent::validateDialogForm($form, $form_state);

    $color = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
      'color',
    ]);

    if ($color && !ctype_xdigit($color)) {
      $form_state->setError($form['attributes']['data-entity-embed-display-settings']['color'], t('Invalid Color String'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    if (!empty($element['#display_settings'])) {
      $field = static::getMediaSourceField($element['#media']);
      foreach ($element['#display_settings'] as $key => $value) {
        if ($key == 'color' || $key == 'class' || empty($value)) {
          continue;
        }
        $element[$field][0]['children']['#query'][$key] = $value;
      }
    }
    return parent::preRender($element);
  }

}
