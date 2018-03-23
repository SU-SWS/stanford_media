<?php

namespace Drupal\stanford_media;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

abstract class MediaEmbedDialogBase extends PluginBase implements MediaEmbedDialogInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constant key in the embed dialog.
   *
   * @var string
   */
  protected $settingsKey = 'data-entity-embed-display-settings';

  /**
   * Media entity being embeded.
   *
   * @var MediaInterface
   */
  protected $entity;

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
    if (method_exists($this, 'validateDialogForm')) {
      array_unshift($form['#validate'], [
        get_class($this),
        'validateDialogForm',
      ]);
    }
    if (method_exists($this, 'submitDialogForm')) {
      array_unshift($form['#submit'], [get_class($this), 'submitDialogForm']);
    }
  }

  /**
   * Validate the dialog form.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  public static function validateDialogForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit handler for the dialog form.
   *
   * @param array $form
   *   Original Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Form State.
   */
  public static function submitDialogForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity, array &$context) {
    $build['entity']['#display_settings'] = $context['data-entity-embed-display-settings'];
    $build['entity']['#pre_render'][] = [get_class($this), 'preRender'];
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
      if (isset($editor_object[$this->settingsKey])) {
        $display_settings = Json::decode($editor_object[$this->settingsKey]);
        $input = $display_settings ?: [];
      }
    }

    return $input + $this->getDefaultInput();
  }

}
