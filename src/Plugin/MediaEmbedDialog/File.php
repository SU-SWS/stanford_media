<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;

/**
 * Changes embedded file media items.
 *
 * @MediaEmbedDialog(
 *   id = "file",
 * )
 */
class File extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->bundle() == 'file' || $this->entity->bundle() == 'document';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => $input['description'] ?? $this->entity->label(),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state) {
    if ($description = $form_state->getValue('description')) {
      $values['attributes']['data-display-description'] = $description;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity) {
    parent::embedAlter($build, $entity);
    if (!empty($build['#attributes']['data-display-description'])) {
      $source_field = static::getMediaSourceField($build['#media']);
      $build[$source_field][0]['#description'] = $build['#attributes']['data-display-description'];
      // Set a shortened hash key for unique configuration.
      $build['#cache']['keys'][] = substr(md5($build['#attributes']['data-display-description']), 0, 5);
      unset($build['#attributes']['data-display-description']);
    }
  }

}
