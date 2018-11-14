<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\file\Entity\File;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
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
   * Media duplicate validation manager service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

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
      $container->get('dropzonejs.upload_save'),
      $container->get('plugin.manager.media_duplicate_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, MessengerInterface $messenger, DropzoneJsUploadSave $dropzone_save, MediaDuplicateValidationManager $duplication_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bundles, $current_user, $messenger);
    $this->dropzoneJsSave = $dropzone_save;
    $this->duplicationManager = $duplication_manager;
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

    $allowed_bundles = $this->getAllowedBundles($form_state);
    $allowed_extensions = $this->bundleSuggestion->getMultipleBundleExtensions($allowed_bundles);

    $form['upload'] = [
      '#title' => $this->t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $this->configuration['dropzone_description'],
      '#max_filesize' => $this->bundleSuggestion->getMaxFilesize(),
      '#extensions' => $allowed_extensions,
      '#max_files' => $form_state->get([
        'entity_browser',
        'validators',
        'cardinality',
        'cardinality',
      ]) ?: 1,
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
      $this->buildSuggestionForm($form, $form_state);
    }

    return $form;
  }

  protected function buildSuggestionForm(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->get([
      'entity_browser',
      'selected_entities',
    ]);
    /** @var \Drupal\media\MediaInterface $entity */
    foreach ($selected_entities as $entity) {
      $file = File::load($entity->getSource()->getSourceFieldValue($entity));

      $similar_items = [];
      foreach ($this->duplicationManager->getDefinitions() as $definition) {
        /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
        $plugin = $this->duplicationManager->createInstance($definition['id']);
        $similar_items = array_merge($similar_items, $plugin->getSimilarItems($file->getFileUri()));
      }

      if (empty($similar_items)) {
        continue;
      }

      $form_state->set(['entity_browser', 'selected_entities'], []);

      \Drupal::messenger()
        ->addWarning($this->t('Similar items found for the file %filename', ['%filename' => basename($file->getFileUri())]));
      $similar_items = array_slice($similar_items, 0, 3);
      $form['similar_items'] = [
        '#type' => 'details',
        '#title' => $this->t('Similar Items'),
        '#open' => TRUE,
        '#weight' => -10,
      ];

      foreach ($similar_items as $similar_item) {
        if ($similar_item->id() == $entity->id()) {
          continue;
        }
        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $item_render = $view_builder->view($entity, 'preview');
        $form['similar_items'][$similar_item->id()]['select'] = [
          '#type' => 'submit',
          '#value' => $this->t('Use %name', ['%name' => $similar_item->label()]),
          '#submit' => [[$this, 'similarItemSubmit']],
          '#mid' => $similar_item->id(),
        ];
        $form['similar_items'][$similar_item->id()]['preview'] = $item_render;
      }

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['use_new'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add New'),
        '#submit' => [[$this, 'uploadNewSubmit']],
      ];
    }
    dpm($form);

    //    $this->selectEntities([], $form_state);
  }

  public function uploadNewSubmit(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('this')->alert(__LINE__);
    dpm(__LINE__);
  }

  public function similarItemSubmit(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('this')->alert(__LINE__);
    dpm(__LINE__);
  }

  /**
   * Gets uploaded files.
   *
   * We implement this to allow child classes to operate on different entity
   * type while still having access to the files in the validate callback here.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
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
  //
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function validate(array &$form, FormStateInterface $form_state) {
  //    $files = $form_state->getValue(['upload', 'uploaded_files']);
  //
  //    foreach ($this->duplicationManager->getDefinitions() as $definition) {
  //      /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
  //      $plugin = $this->duplicationManager->createInstance($definition['id']);
  //
  //      $form_state->set('media_original_upload', $files);
  //      foreach ($files as $delta => $file) {
  //        if (!$plugin->isUnique($file['path'])) {
  //          $form_state->set('media_similar_items', $plugin->getSimilarItems($file['path']));
  //
  //          $form_state->setError($form['widget']['upload'], $this->t('The file "@name" already exists @count times', [
  //            '@name' => basename($file['path']),
  //            '@count' => count($form_state->get('media_similar_items')),
  //          ]));
  //        }
  //      }
  //    }
  //
  //    // Dont create the media entities if any errors exist.
  //    if (!$form_state::hasAnyErrors()) {
  //      parent::validate($form, $form_state);
  //    }
  //  }

}
