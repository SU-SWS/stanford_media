<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\stanford_media\Plugin\media\Source\GoogleForm;

/**
 * Media library add google form input form.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryGoogleFormForm extends AddFormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_google_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);
    if (!$media_type->getSource() instanceof GoogleForm) {
      throw new \InvalidArgumentException('Can only add media types that use Google Forms source.');
    }
    return $media_type;
  }

  /**
   * {@inheritDoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // This was taken from \Drupal\media_library\Form\OembedForm.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#description' => $this->t('Google Forms only works if the form does not contain any file upload fields.'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://docs.google.com/forms/',
      ],
    ];

    $ajax_query = $this->getMediaLibraryState($form_state)->all();
    $ajax_query += [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE];
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => ['query' => $ajax_query],
      ],
    ];

    return $form;
  }

  /**
   * Validates the oEmbed URL.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state): void {
    $url = $form_state->getValue('url');
    preg_match('/^http.*google.*forms\/([^ ]*)\/viewform/', $url, $form_id);
    if (empty($form_id)) {
      $form_state->setErrorByName('url', $this->t('Invalid google forms url.'));
    }
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state): void {
    $this->processInputValues([$form_state->getValue('url')], $form, $form_state);
  }

}
