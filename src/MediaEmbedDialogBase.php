<?php

namespace Drupal\stanford_media;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class MediaEmbedDialogBase.
 *
 * @package Drupal\stanford_media
 */
abstract class MediaEmbedDialogBase extends PluginBase implements MediaEmbedDialogInterface, ContainerFactoryPluginInterface {

  /**
   * Load entity types like the image styles or others.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Media entity being embeded.
   *
   * @var MediaInterface
   */
  protected $entity;

  /**
   * Constant key in the embed dialog.
   *
   * @var string
   */
  protected $settingsKey = 'data-entity-embed-display-settings';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
    $this->entity = $configuration['entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    // Unsets the back action buttons since it doesn't do anything from inside
    // the dialog form. This will prevent users from accidentally going back to
    // the media browser listing modal.
    unset($form['actions']['back']);

    // Hide captions from all forms unless the plugin changes it.
    if (!empty($form['attributes']['data-caption'])) {
      $form['attributes']['data-caption']['#type'] = 'hidden';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDialogForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public static function submitDialogForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity, array &$context) {
  }

  /**
   * Get the field configured for the source data of the media entity.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity.
   *
   * @return string
   *   The configured field name.
   */
  public static function getMediaSourceField(MediaInterface $entity) {
    return $entity->getSource()->getConfiguration()['source_field'];
  }

  /**
   * Get the user's configured settings combined with the default settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state that contains the display settings.
   *
   * @return array|mixed
   *   Input settings with defaults added.
   */
  protected function getUserInput(FormStateInterface $form_state) {
    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      if (isset($editor_object[MediaEmbedDialogInterface::SETTINGS_KEY])) {
        $display_settings = Json::decode($editor_object[MediaEmbedDialogInterface::SETTINGS_KEY]);
        $input = $display_settings ?: [];
      }
    }

    return $input + $this->getDefaultInput();
  }

}
