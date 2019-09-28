<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\media\MediaInterface;
use Drupal\media_library\Form\FileUploadForm;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides media library file upload form to extend its functionality.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryFileUploadForm extends FileUploadForm {

  /**
   * DropzoneJs upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSaveInterface
   */
  protected $dropzoneSave;

  /**
   * {@inheritDoc}}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('element_info'),
      $container->get('renderer'),
      $container->get('file_system'),
      $container->get('dropzonejs.upload_save'),
      $container->get('media_library.opener_resolver')
    );
  }

  /**
   * {@inheritDoc}}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, ElementInfoManagerInterface $element_info, RendererInterface $renderer, FileSystemInterface $file_system, DropzoneJsUploadSaveInterface $dropzone_upload, OpenerResolverInterface $opener_resolver = NULL) {
    parent::__construct($entity_type_manager, $library_ui_builder, $element_info, $renderer, $file_system, $opener_resolver);
    $this->dropzoneSave = $dropzone_upload;
  }

  /**
   * {@inheritDoc}}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $element = parent::buildInputElement($form, $form_state);

    $element['container']['dropzone'] = [
      '#title' => $element['container']['upload']['#title'],
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $this->t('Drop files here to upload them'),
      '#max_filesize' => $element['container']['upload']['#upload_validators']['file_validate_size'][0],
      '#extensions' => $element['container']['upload']['#upload_validators']['file_validate_extensions'][0],
      '#max_files' => $element['container']['upload']['#remaining_slots'],
      '#clientside_resize' => FALSE,
    ];
    $element['container']['continue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload and Continue'),
      '#submit' => ['::uploadDropzoneSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
        ],
      ],
    ];

    $element['#attached']['library'][] = 'dropzonejs/widget';
    $element['container']['upload']['#type'] = 'hidden';
    unset($element['container']['upload']['#process']);
    return $element;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function uploadDropzoneSubmit(array $form, FormStateInterface $form_state) {
    $files = [];
    foreach ($form_state->getValue(['dropzone', 'uploaded_files']) as $file) {
      $files[] = $this->dropzoneSave->createFile(
        $file['path'],
        'public://',
        $form['container']['dropzone']['#extensions'],
        \Drupal::currentUser()
      );
    }
    $this->processInputValues($files, $form, $form_state);
  }

  /**
   * {@inheritDoc}}
   */
  public function validateUploadElement(array $element, FormStateInterface $form_state) {
    $form_state->setValue('upload', ['fids' => []]);
    return parent::validateUploadElement($element, $form_state);
  }

  /**
   * {@inheritDoc}}
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    $element = parent::buildEntityFormElement($media, $form, $form_state, $delta);

    // If similar media items already exist, present those options to the user.
    if ($similar_options = $this->getSimilarMediaOptions($media)) {
      $element['similar_media']['#tree'] = TRUE;
      // We don't set a default value on this field and we require it so that
      // it forces the user to make a decision.
      $element['similar_media'][$delta] = [
        '#type' => 'radios',
        '#required' => TRUE,
        '#title' => $this->t('Possible similar items'),
        '#description' => $this->t('These files already exist on the site. You can use one of these items or continue to upload a new file. These are only possible suggestions.'),
        '#options' => $similar_options + [$this->t('Add new')],
        '#weight' => -10,
        '#attached' => ['library' => ['stanford_media/admin']],
        '#prefix' => '<div class="similar-media-options">',
        '#suffix' => '</div>',
      ];
    }
    return $element;
  }

  /**
   * Get the available media options for the radio buttons.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item to compare.
   *
   * @return array
   *   Similar media rendered options for radios.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getSimilarMediaOptions(MediaInterface $media) {
    $options = [];
    $duplication_service = self::getMediaDuplicationService();

    // Service doesn't exist.
    if (!$duplication_service) {
      return $options;
    }

    $similar_items = $duplication_service->getSimilarEntities($media, 3);
    $media_view_builder = $this->entityTypeManager->getViewBuilder('media');

    // Build a rendering of the media entities for preview options.
    foreach ($similar_items as $similar_media) {
      $media_display = $media_view_builder->view($similar_media, 'media_library');

      $options[$similar_media->id()] = '<div class="media-label label">';
      $options[$similar_media->id()] .= $this->t('Use %name', ['%name' => $similar_media->label()])
        ->render();
      $options[$similar_media->id()] .= '</div>';
      $options[$similar_media->id()] .= self::getRenderDisplay($media_display);
    }

    return $options;
  }

  /**
   * {@inheritDoc}}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $similar_choices = $form_state->getValue('similar_media', []);
    // Unset the similar media choices because it causes conflicts later.
    $form_state->unsetValue('similar_media');
    parent::validateForm($form, $form_state);

    // When the user chooses to use an existing item, set that media item into
    // the form state.
    foreach ($similar_choices as $delta => $similar_choice) {
      if ($similar_choice) {
        $selected_media = $this->entityTypeManager->getStorage('media')
          ->load($similar_choice);
        $form_state->set(['media', $delta], $selected_media);
      }
    }
  }

  /**
   * Get the rendered result of a render array.
   *
   * @param array $render_array
   *   Entity render array.
   *
   * @return string
   *   Rendered contents.
   *
   * @codeCoverageIgnore
   *   Ignore this because `render()` is not established during unit tests.
   */
  protected static function getRenderDisplay(array &$render_array) {
    return render($render_array);
  }

  /**
   * Get the media duplication service if its enabled.
   *
   * @return \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager|null
   *   Duplication plugin manager service.
   */
  protected static function getMediaDuplicationService() {
    if (\Drupal::hasService('plugin.manager.media_duplicate_validation')) {
      return \Drupal::service('plugin.manager.media_duplicate_validation');
    }
  }

}
