<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media_library\Form\FileUploadForm;

/**
 * Overrides media library file upload form to provide a dropzone js solution.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryFileUploadForm extends FileUploadForm {

  /**
   * {@inheritDoc}}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $element = parent::buildInputElement($form, $form_state);

    $element['container']['dropzonejs'] = [
      '#title' => $element['container']['upload']['#title'],
      '#type' => 'dropzonejs',
      '#dropzone_description' => $this->t('Drop files here to upload them.'),
      '#max_filesize' => $element['container']['upload']['#upload_validators']['file_validate_size'][0],
      '#extensions' => $element['container']['upload']['#upload_validators']['file_validate_extensions'][0],
      '#max_files' => $element['container']['upload']['#cardinality'],
      '#clientside_resize' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritDoc}}
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    // TODO: Add focal point to images.

    $element = parent::buildEntityFormElement($media, $form, $form_state, $delta);
    $duplication_service = self::getMediaDuplicationService();

    if ($duplication_service) {
      $similar_items = $duplication_service->getSimilarEntities($media, 3);
      if (empty($similar_items)) {
        return $element;
      }

      $element = ['uploaded_form' => $element];
      $element['uploaded_form']['#states'] = [
        'invisible' => ['' => ['']],
      ];

      $media_view_builder = $this->entityTypeManager->getViewBuilder('media');
      $options = [];
      foreach ($similar_items as $similar_media) {
        $media_display = $media_view_builder->view($similar_media, 'preview');
        $options[$similar_media->id()] = '<div class="media-label label">';
        $options[$similar_media->id()] .= $this->t('Use %name', ['%name' => $similar_media->label()])
          ->render();
        $options[$similar_media->id()] .= '</div>';
        $options[$similar_media->id()] .= $this->getRenderDisplay($media_display);
      }

      $element['similar_media'] = [
        '#type' => 'radios',
        '#title' => $this->t('Existing similar items'),
        '#options' => $options + [$this->t('Add new')],
        '#weight' => -10,
      ];

    }
    return $element;
  }

  /**
   * {@inheritDoc}}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($selected_similar = $form_state->getValue('similar_media')) {
      $selected_media = $this->entityTypeManager->getStorage('media')
        ->load($selected_similar);
      $form_state->set('media', [$selected_media]);
      return;
    }
    foreach ($this->getAddedMediaItems($form_state) as $delta => $media) {
      if (isset($form['media'][$delta]['uploaded_form'])) {
        $form['media'][$delta] = $form['media'][$delta]['uploaded_form'];
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('similar_media')) {
      return;
    }
    parent::submitForm($form, $form_state);
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
   */
  protected function getRenderDisplay(array &$render_array) {
    return render($render_array);
  }

  /**
   * @return \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager|null
   */
  protected static function getMediaDuplicationService() {
    if (\Drupal::hasService('plugin.manager.media_duplicate_validation')) {
      return \Drupal::service('plugin.manager.media_duplicate_validation');
    }
  }

}
