<?php

namespace Drupal\stanford_media\Plugin;

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
   * @var \Drupal\media\MediaInterface
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
  public function getDefaultInput(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function validateDialogForm(array $form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity): void {
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
  public static function getMediaSourceField(MediaInterface $entity): string {
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
      $input = $editor_object['attributes'] ?? [];
    }

    return $input + $this->getDefaultInput();
  }

}
