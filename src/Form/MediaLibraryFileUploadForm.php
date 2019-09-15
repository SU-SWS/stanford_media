<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media_library\Form\FileUploadForm;

/**
 * Overrides media library file upload form to extend its functionality.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryFileUploadForm extends FileUploadForm {

  /**
   * {@inheritDoc}}
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    $element = parent::buildEntityFormElement($media, $form, $form_state, $delta);

    // If similar media items already exist, present those options to the user.
    if ($similar_options = $this->getSimilarMediaOptions($media)) {
      // We don't set a default value on this field and we require it so that
      // it forces the user to make a decision.
      $element['similar_media'] = [
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
    // When the user chooses to use an existing item, set that media item into
    // the form state.
    if ($selected_similar = $form_state->getValue('similar_media')) {
      $selected_media = $this->entityTypeManager->getStorage('media')
        ->load($selected_similar);
      $form_state->set('media', [$selected_media]);
      return;
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The user chose to use an existing item instead of adding a new one. So
    // we can skip saving the media entity.
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
