<?php

namespace Drupal\stanford_media;

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
interface MediaEmbedDialogInterface {

  /**
   * Check if the given plugin is applicable for this media item.
   *
   * @return bool
   *   Plugin is applicable.
   */
  function isApplicable();

  /**
   * Get the default input values of the plugin form.
   *
   * @return mixed
   */
  function getDefaultInput();

  /**
   * Alter the dialog form in any neccessary way and add validation & submit.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  function alterDialogForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate the dialog form.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  static function validateDialogForm(array &$form, FormStateInterface $form_state);

  /**
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  static function submitDialogForm(array &$form, FormStateInterface $form_state);

  /**
   * Add the prerender method to the media entity embed.
   *
   * @param array $build
   *   The media entity build array.
   * @param \Drupal\media\MediaInterface $entity
   *   Selected media entity.
   * @param array $context
   *   Context containing the display settings from the embed.
   */
  function embedAlter(array &$build, MediaInterface $entity, array &$context);

  /**
   * Alter the medial element.
   *
   * @param array $element
   *   Original media render array.
   *
   * @return array
   *   Altered render array.
   */
  static function preRender(array $element);

}
