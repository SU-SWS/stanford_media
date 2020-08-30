<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\OEmbedForm;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;

/**
 * Media library add stanford embed input form.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryEmbeddableForm extends OEmbedForm {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_embeddable';
  }

  /**
   * {@inheritDoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // This was taken from \Drupal\media_library\Form\OembedForm.
    $media_type = $this->getMediaType($form_state);
    $providers = $media_type->getSource()->getProviders();

    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['field_media_embeddable_oembed'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#description' => $this->t('Works with oEmbed providers.'),
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => 'https://something.com',
      ],
    ];

    $form['container']['field_media_embeddable_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed Code'),
      '#description' => $this->t('Use this field to paste in embed codes which are not available through oEmbed'),
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => '',
      ],
    ];



    $ajax_query = $this->getMediaLibraryState($form_state)->all();
    $ajax_query += [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE];
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateEmbeddable'],
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
  public function validateEmbeddable(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('field_media_embeddable_oembed');
    $embed_code = $form_state->getValue('field_media_embeddable_code');

    // no validation on the URL if we have an embed code.
    if ($embed_code) {
      return;
    }

    if ($url) {
      try {
        $resource_url = $this->urlResolver->getResourceUrl($url);
        $this->resourceFetcher->fetchResource($resource_url);
      }
      catch (ResourceException $e) {
        $form_state->setErrorByName('url', $e->getMessage());
      }
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
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $values = [
      $form_state->getValue('field_media_embeddable_oembed'),
    ];
    $this->processInputValues($values, $form, $form_state);
  }


  /**
   * Creates media items from source field input values.
   *
   * @param mixed[] $source_field_values
   *   The values for source fields of the media items.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected function processInputValues(array $source_field_values, array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);

    dpm($media_type);

    $media_storage = $this->entityTypeManager->getStorage('media');
    $source_field_name = $this->getSourceFieldName($media_type);
    $media = array_map(function ($source_field_value) use ($media_type, $media_storage, $source_field_name) {
      return $this->createMediaFromValue($media_type, $media_storage, $source_field_name, $source_field_value);
    }, $source_field_values);
    // Re-key the media items before setting them in the form state.
    $form_state->set('media', array_values($media));
    // Save the selected items in the form state so they are remembered when an
    // item is removed.
    $media = $this->entityTypeManager->getStorage('media')
      ->loadMultiple(explode(',', $form_state->getValue('current_selection')));
    // Any ID can be passed to the form, so we have to check access.
    $form_state->set('current_selection', array_filter($media, function ($media_item) {
      return $media_item->access('view');
    }));
    $form_state->setRebuild();
  }

}
