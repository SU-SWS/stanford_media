<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;

/**
 * Changes embedded file media items.
 *
 * @MediaEmbedDialog(
 *   id = "file"
 * )
 */
class File extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->bundle() == 'file';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput(): array {
    $input = ['data-display-description' => NULL];
    return $input + parent::getDefaultInput();
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state): void {
    parent::alterDialogForm($form, $form_state);
    $user_input = $this->getUserInput($form_state);
    unset($form['caption']);
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optionally enter text to use as the link text.'),
      '#default_value' => $user_input['data-display-description'] ?: $this->entity->label(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state): void {
    $values['attributes']['data-display-description'] = $form_state->getValue('description');
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity): void {
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
