<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\file\Entity\File;
use Drupal\stanford_media\BundleSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs_media",
 *   label = @Translation("Media Entity DropzoneJS with edit (all bundles)"),
 *   description = @Translation("Adds DropzoneJS upload integration that saves
 *   Media entities and allows to edit them.")
 * )
 */
class DropzoneUpload extends MediaBrowserBase {

  /**
   * Dropzone upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSave
   */
  protected $dropzoneJsSave;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('stanford_media.bundle_suggestion'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('dropzonejs.upload_save')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, MessengerInterface $messenger, DropzoneJsUploadSave $dropzone_save) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bundles, $current_user, $messenger);
    $this->dropzoneJsSave = $dropzone_save;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'auto_select' => FALSE,
      'upload_location' => 'public://media',
      'dropzone_description' => $this->t('Drop files here to upload them'),
    ];
    return $config + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->configuration;

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $configuration['upload_location'],
    ];

    $form['dropzone_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dropzone drag-n-drop zone text'),
      '#default_value' => $configuration['dropzone_description'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $media_entities = [];

    if ($form_state->get(['dropzonejs', $this->uuid(), 'media'])) {
      return $form_state->get(['dropzonejs', $this->uuid(), 'media']);
    }

    // Get the files and make media entities.
    foreach ($this->getFiles($form, $form_state) as $file) {
      if ($file instanceof File) {
        /** @var \Drupal\media\Entity\MediaType $media_type */
        $media_type = $this->bundleSuggestion->getBundleFromFile($file->getFileUri());
        $media = $this->prepareMediaEntity($media_type, $file);
        $media->save();
        $media_entities[] = $media;
      }
    }

    $form_state->set(['dropzonejs', $this->uuid(), 'media'], $media_entities);
    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $storage = $form_state->getStorage();

    $allowed_bundles = $this->getAllowedBundles($form_state);
    $allowed_extensions = $this->bundleSuggestion->getMultipleBundleExtensions($allowed_bundles);

    $form['upload'] = [
      '#title' => $this->t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $this->configuration['dropzone_description'],
      '#max_filesize' => $this->bundleSuggestion->getMaxFilesize(),
      '#extensions' => $allowed_extensions,
      '#max_files' => !empty($storage['entity_browser']['validators']['cardinality']['cardinality']) ? $storage['entity_browser']['validators']['cardinality']['cardinality'] : 1,
      '#clientside_resize' => FALSE,
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';
    $form['#attached']['library'][] = 'stanford_media/dropzonejs';

    // Remove the upload after we have some files.
    if ($form_state->get(['dropzonejs', $this->uuid(), 'media'])) {
      $form['upload']['#type'] = 'hidden';
    }

    return $form;
  }

  /**
   * Gets uploaded files.
   *
   * We implement this to allow child classes to operate on different entity
   * type while still having access to the files in the validate callback here.
   *
   * @param array $form
   *   Form structure.
   * @param FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\file\FileInterface[]
   *   Array of uploaded files.
   */
  protected function getFiles(array $form, FormStateInterface $form_state) {
    $files = $form_state->get(['dropzonejs', $this->uuid(), 'files']) ?: [];

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue([
      'upload',
      'uploaded_files',
    ], []) as $file) {
      if (!empty($file['path']) && file_exists($file['path'])) {
        $bundle = $this->bundleSuggestion->getBundleFromFile($file['path']);
        $additional_validators = [
          'file_validate_size' => [
            $this->bundleSuggestion->getMaxFileSizeBundle($bundle),
            0,
          ],
        ];

        $entity = $this->dropzoneJsSave->createFile(
          $file['path'],
          $this->configuration['upload_location'],
          $this->bundleSuggestion->getAllExtensions(),
          $this->currentUser,
          $additional_validators
        );
        $files[] = $entity;
      }
    }

    if (!empty($form['widget']['upload']['#max_files']) && $form['widget']['upload']['#max_files']) {
      $files = array_slice($files, -$form['widget']['upload']['#max_files']);
    }

    $form_state->set(['dropzonejs', $this->uuid(), 'files'], $files);

    return $files;
  }

}
