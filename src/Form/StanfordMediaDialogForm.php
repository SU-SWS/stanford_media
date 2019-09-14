<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\media\Form\EditorMediaDialog;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StanfordMediaDialogForm extends EditorMediaDialog {

  protected $dialogPluginManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('plugin.manager.media_embed_dialog_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, MediaEmbedDialogManager $dialog_plugin_manager) {
    parent::__construct($entity_repository);
    $this->dialogPluginManager = $dialog_plugin_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    $form = parent::buildForm($form, $form_state, $editor);
    $media = $this->getFormMediaEntity($form_state);
    foreach ($this->getDialogAlterPlugins($media) as $plugin) {
      $plugin->alterDialogForm($form, $form_state, $editor);
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $orig_response = parent::submitForm($form, $form_state);
    $new_response = new AjaxResponse();
    $commands = $orig_response->getCommands();
    if ($commands[0]['command'] == 'editorDialogSave') {
      $values = $commands[0]['values'];

      $media = $this->getFormMediaEntity($form_state);

      foreach ($this->getDialogAlterPlugins($media) as $plugin) {
        $plugin->alterDialogValues($values, $form, $form_state);
      }

      $new_response->addCommand(new EditorDialogSave($values));
      $new_response->addCommand(new CloseModalDialogCommand());
    }
    return $new_response;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\media\MediaInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getFormMediaEntity(FormStateInterface $form_state) {
    $media_uuid = $form_state->get(['media_embed_element', 'data-entity-uuid']);
    return $this->entityRepository->loadEntityByUuid('media', $media_uuid);
  }

  /**
   * @param \Drupal\media\MediaInterface $media
   *
   * @return \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface[]
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getDialogAlterPlugins(MediaInterface $media) {
    $plugins = [];
    foreach (array_keys($this->dialogPluginManager->getDefinitions()) as $plugin_id) {
      /** @var \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface $plugin */
      $plugin = $this->dialogPluginManager->createInstance($plugin_id, ['entity' => $media]);

      if ($plugin->isApplicable()) {
        $plugins[] = $plugin;
      }
    }
    return $plugins;
  }

}
