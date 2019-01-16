<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;

/**
 * Changes embedded file media items.
 *
 * @MediaEmbedDialog(
 *   id = "soundcloud",
 *   media_type = "audio"
 * )
 */
class SoundCloud extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface && $this->entity->bundle() == 'audio') {
      $source_field = static::getMediaSourceField($this->entity);
      $field_value = $this->entity->get($source_field)->getString();
      return strpos($field_value, 'soundcloud') !== FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    // Restore caption text field.
    $form['attributes']['data-caption']['#type'] = 'textfield';
    $input = $this->getUserInput($form_state);

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#description' => $this->t('Choose which style of embed player to use.'),
      '#default_value' => $input['style'] ?: 'classic',
      '#options' => [
        'classic' => $this->t('Classic Embed'),
        'visual' => $this->t('Visual Embed'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    if ($element['#display_settings']['style'] == 'classic') {
      $source_field = static::getMediaSourceField($element['#media']);
      $element[$source_field][0]['children']['#query']['visual'] = 'false';
    }
    return $element;
  }

}
