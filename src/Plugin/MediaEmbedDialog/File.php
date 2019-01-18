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
 *   id = "file",
 *   media_type = "file"
 * )
 */
class File extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->bundle() == 'file';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => $input['description'] ?: $this->entity->label(),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    $source_field = static::getMediaSourceField($element['#media']);
    $element[$source_field][0]['#description'] = $element['#display_settings']['description'];
    return $element;
  }

}
