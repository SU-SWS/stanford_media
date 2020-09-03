<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\media_library\Form\OEmbedForm;

/**
 * Media library add stanford embed input form.
 *
 * @package Drupal\stanford_media\Form
 */
class MediaLibraryEmbeddableForm extends OEmbedForm {

  protected $currentUser;


  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, UrlResolverInterface $url_resolver, ResourceFetcherInterface $resource_fetcher, OpenerResolverInterface $opener_resolver = NULL, AccountInterface $account) {
    parent::__construct($entity_type_manager, $library_ui_builder, $url_resolver, $resource_fetcher, $opener_resolver);
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

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
    // This was adapted from \Drupal\media_library\Form\OembedForm.
    $authorized_for_unstructured = $this->currentUser->hasPermission('create field_media_embeddable_code') || $user->hasPermission('edit field_media_embeddable_code');

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
      '#description' => $this->t('Allowed providers: @providers. For custom embeds, please request support.', [
        '@providers' => implode(', ', $providers),
      ]),
      '#attributes' => [
        'placeholder' => 'https://',
      ],
    ];

    $form['container']['field_media_embeddable_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed Code'),
      '#description' => $this->t('Use this field to paste in embed codes which are not available through oEmbed'),
      '#access' => $authorized_for_unstructured,
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
   * Informs us if we are working with an unstructured embed.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @return bool
   *   True if unstructured, otherwise false.
   */
  protected function isUnstructured(FormStateInterface $form_state) {
    return !empty($form_state->getValue('field_media_embeddable_code'));
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
    // No validation necessary if we have an embed code.
    if ($this->isUnstructured($form_state)) {
      return;
    }
    parent::validateUrl($form, $form_state);
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
    if ($this->isUnstructured($form_state)) {
      $values = [$form_state->getValue('field_media_embeddable_code')];
    } else {
      $values = [$form_state->getValue('field_media_embeddable_oembed')];
    }
    $this->processInputValues($values, $form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function processInputValues(array $source_field_values, array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);
    $media_storage = $this->entityTypeManager->getStorage('media');
    if ($this->isUnstructured($form_state)) {
      $source_field_name = 'field_media_embeddable_code';
    } else {
      $source_field_name = 'field_media_embeddable_oembed';
    }
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
