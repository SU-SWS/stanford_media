<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;

/**
 * Defines the interface for entity browser displays.
 *
 * Display plugins determine how a complete entity browser is delivered to the
 * user. They wrap around and encapsulate the entity browser. Examples include:
 *
 * - Displaying the entity browser on its own standalone page.
 * - Displaying the entity browser in an iframe.
 * - Displaying the entity browser in a modal dialog box.
 */
interface MediaEmbedDialogInterface extends PluginInspectionInterface {

  const SETTINGS_KEY = 'data-entity-embed-display-settings';

  /**
   * Check if the given plugin is applicable for this media item.
   *
   * @return bool
   *   Plugin is applicable.
   */
  public function isApplicable();

  /**
   * Get the default form values for the plugin form..
   *
   * @return array
   *   Key value paired array of default configuration values.
   */
  public function getDefaultInput();

  /**
   * Alter the dialog form in any neccessary way and add validation & submit.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state);

  /**
   * @param array $values
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state);

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
  public function embedAlter(array &$build, MediaInterface $entity);

}
