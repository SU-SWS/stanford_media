<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;

/**
 * Modifies all embed dialogs for all media types.
 *
 * @MediaEmbedDialog(
 *   id = "all_media"
 * )
 */
class Media extends MediaEmbedDialogBase {

  /**
   * {@inheritDoc}
   */
  public function isApplicable(): bool {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state): void {
    parent::alterDialogForm($form, $form_state);
    // The text editor is not configured to allow a choice of view modes.
    if (!isset($form['view_mode'])) {
      return;
    }

    $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $entity_bundle = $this->entity->bundle();

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $form_state->getBuildInfo()['args'][0];
    $filter_config = $editor->getFilterFormat()
      ->filters('media_embed')
      ->getConfiguration();

    // If a view mode is not configured for the current media bundle, remove it
    // from an option to the user.
    foreach (array_keys($form['view_mode']['#options']) as $display_mode_id) {
      if (
        $display_mode_id != $filter_config['settings']['default_view_mode'] &&
        !$display_storage->load("media.$entity_bundle.$display_mode_id")
      ) {
        unset($form['view_mode']['#options'][$display_mode_id]);
      }
    }

    // If there aren't enough options to choose form, just hide the field input.
    if (count($form['view_mode']['#options']) <= 1) {
      $form['view_mode']['#type'] = 'hidden';
      $form['view_mode']['#value'] = $form['view_mode']['#default_value'];
    }
  }

}
