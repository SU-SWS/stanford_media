<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\media\Form\EditorMediaDialog;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides Media modal dialog form to allow plugins to change it.
 *
 * @package Drupal\stanford_media\Form
 */
class StanfordMediaDialogForm extends EditorMediaDialog {

  /**
   * Dialog plugin manager service.
   *
   * @var \Drupal\stanford_media\Plugin\MediaEmbedDialogManager
   */
  protected $dialogPluginManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.media_embed_dialog_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityDisplayRepositoryInterface $display_repository, MediaEmbedDialogManager $dialog_manager) {
    parent::__construct($entity_repository, $display_repository);
    $this->dialogPluginManager = $dialog_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    $form = parent::buildForm($form, $form_state, $editor);
    $media = $this->getFormMediaEntity($form_state);
    foreach ($this->getDialogAlterPlugins($media) as $plugin) {
      $plugin->alterDialogForm($form, $form_state);
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $media = $this->getFormMediaEntity($form_state);
    foreach ($this->getDialogAlterPlugins($media) as $plugin) {
      $plugin->validateDialogForm($form, $form_state);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = parent::submitForm($form, $form_state);
    $commands = $response->getCommands();

    if ($commands[0]['command'] == 'editorDialogSave') {
      $values = $commands[0]['values'];

      $media = $this->getFormMediaEntity($form_state);

      foreach ($this->getDialogAlterPlugins($media) as $plugin) {
        $plugin->alterDialogValues($values, $form, $form_state);
      }

      $response = new AjaxResponse();
      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

  /**
   * Get the entity UUID and load the media entity associated to it.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\media\MediaInterface|null
   *   Media entity in the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getFormMediaEntity(FormStateInterface $form_state): ?MediaInterface {
    $media_uuid = $form_state->get(['media_embed_element', 'data-entity-uuid']);
    return $this->entityRepository->loadEntityByUuid('media', $media_uuid);
  }

  /**
   * Get an array of all plugins that are supposed to alter the dialog.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface[]
   *   Array of plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getDialogAlterPlugins(MediaInterface $media): array {
    $plugins = [];
    foreach (array_keys($this->dialogPluginManager->getDefinitions()) as $plugin_id) {
      /** @var \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface $plugin */
      $plugin = $this->dialogPluginManager->createInstance($plugin_id, ['entity' => $media]);

      if ($plugin->isApplicable()) {
        $plugins[$plugin_id] = $plugin;
      }
    }
    return $plugins;
  }

}
