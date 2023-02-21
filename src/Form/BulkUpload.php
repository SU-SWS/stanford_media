<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\dropzonejs\DropzoneJsUploadSave;
use Drupal\file\Entity\File;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\Entity\Media;
use Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkUpload for bulk upload page.
 *
 * @package Drupal\stanford_media\Form
 */
class BulkUpload extends FormBase {

  /**
   * Entity manager used to load media types.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Our bundle suggestion for some helpful methods.
   *
   * @var \Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface
   */
  protected $bundleSuggestion;

  /**
   * Dropzone file save.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSave
   */
  protected $dropzoneSave;

  /**
   * Current user on the site.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Sets messages for the user.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.bundle_suggestion_manager'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_manager, BundleSuggestionManagerInterface $bundle_suggestion, DropzoneJsUploadSave $dropzone_save, AccountProxy $current_user, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_manager;
    $this->bundleSuggestion = $bundle_suggestion;
    $this->dropzoneSave = $dropzone_save;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * Check if the given account has access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access Result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(!empty($this->bundleSuggestion->getUploadBundles()));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stanford_media_bulk_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If files have already been uploaded, we don't want to allow upload again.
    if (empty($form_state->get(['dropzonejs', 'media']))) {
      $form['upload'] = [
        '#title' => $this->t('File upload'),
        '#type' => 'dropzonejs',
        '#required' => TRUE,
        '#dropzone_description' => $this->t('Drop files here to upload them'),
        '#max_filesize' => $this->bundleSuggestion->getMaxFileSize(),
        '#extensions' => implode(' ', $this->bundleSuggestion->getAllExtensions()),
        '#max_files' => 0,
        '#clientside_resize' => FALSE,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 99,
      'submit' => [
        '#type' => 'submit',
        '#value' => isset($form['upload']) ? $this->t('Upload') : $this->t('Save'),
        '#eb_widget_main_submit' => isset($form['upload']),
        '#attributes' => ['class' => ['is-entity-browser-submit']],
        '#button_type' => 'primary',
      ],
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    $form['#attached']['library'][] = 'stanford_media/admin';
    $this->getEntityForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Don't create the media entities if any errors exist.
    if ($form_state::hasAnyErrors()) {
      return;
    }

    // Get the newly created media entities.
    $media_entities = $this->createMediaEntities($form, $form_state);

    // Save the files and the media entites on them.
    foreach ($media_entities as $media_entity) {
      if ($media_entity instanceof Media) {
        $source_field = $media_entity->getSource()
          ->getConfiguration()['source_field'];
        // If we don't save file at this point Media entity creates another file
        // entity with same uri for the thumbnail. That should probably be fixed
        // in Media entity, but this workaround should work for now.
        $media_entity->$source_field->entity->save();
        $media_entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    // Upload is done, but we want to rebuild the form to display the inline
    // entity forms of the media entity.
    if ($trigger['#eb_widget_main_submit']) {
      $form_state->setRebuild();
      return;
    }

    $count = 0;

    $children = Element::children($form['entities']);
    foreach ($children as $child) {
      $entity_form = $form['entities'][$child];

      // Make sure we only get the inline entity form elements.
      if (!isset($entity_form['#ief_element_submit'])) {
        continue;
      }

      // Call all inline entity form submit functions. This saves the entities
      // with the new values from any fields.
      foreach ($entity_form['#ief_element_submit'] as $submit_function) {
        call_user_func_array($submit_function, [&$entity_form, $form_state]);
      }

      $count++;
    }

    // Give a message and redirect the user to the media overview page if they
    // have permission to view that page.
    $this->messenger->addMessage($this->t('Saved %count Media Items', ['%count' => $count]));
    if ($this->currentUser->hasPermission('access media overview')) {
      $url = Url::fromUserInput('/admin/content/media');
      $form_state->setRedirectUrl($url);
    }
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getFiles(array $form, FormStateInterface $form_state): array {

    $files = $form_state->getValue(['dropzonejs', 'files']);

    if (!$files) {
      $files = [];
    }

    $form_files = $form_state->getValue(['upload', 'uploaded_files']);
    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_files as $file) {

      // Check if file exists before we create an entity for it.
      if (!empty($file['path']) && file_exists($file['path'])) {

        // Get the media type from the file extension.
        $media_type = $this->bundleSuggestion->getSuggestedBundle($file['path']);

        if ($media_type) {
          // Validate the media bundle allows for the size of file.
          $max_size = $this->bundleSuggestion->getMaxFileSizeBundle($media_type);

          // Create the file entity.
          $files[] = $this->dropzoneSave->createFile(
            $file['path'],
            $this->bundleSuggestion->getUploadPath($media_type),
            implode(' ', $this->bundleSuggestion->getAllExtensions()),
            $this->currentUser,
            ['file_validate_size' => [$max_size, 0]]
          );
        }
      }
    }

    $form_state->set(['dropzonejs', 'files'], $files);

    return $files;
  }

  /**
   * Add the inline entity form after the files have been uploaded.
   *
   * @param array $form
   *   Original form from getFrom().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityForm(array &$form, FormStateInterface $form_state): void {
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entities'] = [
      '#prefix' => '<div id="entities">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $media_entities = $this->createMediaEntities($form, $form_state);

    if (empty($media_entities)) {
      $form['entities']['#markup'] = NULL;
      return;
    }

    foreach ($media_entities as $entity) {
      $form['entities'][$entity->id()] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#default_value' => $entity,
        '#form_mode' => 'media_browser',
      ];
    }

    // Make sure to add its own submit handler before adding an IEF submit.
    $form['#submit'] = $form['#submit'] ?? [[$this, 'submitForm']];
    // Without this, IEF won't know where to hook into the widget.
    ElementSubmit::addCallback($form['actions']['submit'], $form);
  }

  /**
   * Create media entities out of the uploaded files and their entities.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object.
   *
   * @return \Drupal\media\MediaInterface[]
   *   Array of media entities before saving.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createMediaEntities(array $form, FormStateInterface $form_state): array {
    $media_entities = [];

    // Media entities were already created.
    if ($form_state->get(['dropzonejs', 'media'])) {
      return $form_state->get(['dropzonejs', 'media']);
    }

    $files = $this->getFiles($form, $form_state);
    foreach ($files as $file) {
      if ($file instanceof File) {
        // Get the media type bundle from the file uri.
        $media_type = $this->bundleSuggestion->getSuggestedBundle($file->getFileUri());

        // Create the media entity.
        $media_entities[] = $this->entityTypeManager->getStorage('media')
          ->create([
            'bundle' => $media_type->id(),
            $media_type->getSource()
              ->getConfiguration()['source_field'] => $file,
            'uid' => $this->currentUser->id(),
            'status' => TRUE,
            'type' => $media_type->getSource()->getPluginId(),
          ]);
      }
    }

    $form_state->set(['dropzonejs', 'media'], $media_entities);
    return $media_entities;
  }

}
