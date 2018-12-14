<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Create media entities from embed codes."),
 * )
 */
class EmbedCode extends MediaBrowserBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if ($form_state->get(['embed_code', $this->uuid(), 'media'])) {
      return $form_state->get(['embed_code', $this->uuid(), 'media']);
    }

    $media_entities = [];
    $value = $form_state->getValue('input');

    $media_type = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$value || !$media_type) {
      return [];
    }

    // Create the media item.
    $entity = $this->prepareMediaEntity($media_type, $value);
    if ($entity) {
      $entity->save();
      $media_entities[] = $entity;
    }

    $form_state->set(['embed_code', $this->uuid(), 'media'], $media_entities);
    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $widget_params) {
    $form = parent::getForm($original_form, $form_state, $widget_params);
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shareable Url'),
      '#description' => $this->t('Enter the url to the sharable content. This will display as an embedded content on the page.'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Enter a URL...'),
    ];

    if ($form_state->get(['embed_code', $this->uuid(), 'media'])) {
      $form['input']['#type'] = 'hidden';
    }

    $form['#attached']['library'][] = 'stanford_media/embed';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $value = trim($form_state->getValue('input'));
    $bundle = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$bundle) {
      $form_state->setError($form['widget']['input'], $this->t('Invalid embed string.'));
    }
  }

}
