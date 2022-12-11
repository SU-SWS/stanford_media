<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;

/**
 * Defines the interface for media dialog form plugins.
 */
interface MediaEmbedDialogInterface extends PluginInspectionInterface {

  /**
   * Check if the given plugin is applicable for this media item.
   *
   * @return bool
   *   Plugin is applicable.
   */
  public function isApplicable(): bool;

  /**
   * Get the default form values for the plugin form..
   *
   * @return array
   *   Key value paired array of default configuration values.
   */
  public function getDefaultInput(): array;

  /**
   * Alter the dialog form in any neccessary way and add validation & submit.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state): void;

  /**
   * Alter the dialog values that will be inserted into the <drupal-media>.
   *
   * @param array $values
   *   Keyed array of values.
   * @param array $form
   *   Submitted form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state): void;

  /**
   * Validate the dialog form.
   *
   * @param array $form
   *   Submitted form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function validateDialogForm(array $form, FormStateInterface $form_state): void;

  /**
   * Alter the embed media item before rendering, including adding a preRender.
   *
   * @param array $build
   *   The media entity build array.
   * @param \Drupal\media\MediaInterface $entity
   *   Selected media entity.
   *
   * @see stanford_media_entity_embed_alter()
   */
  public function embedAlter(array &$build, MediaInterface $entity): void;

}
