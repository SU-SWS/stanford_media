<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Drupal\stanford_media\Service\BundleSuggestion;
use Drupal\video_embed_field\ProviderManager as VideoProviderManager;
use Drupal\audio_embed_field\ProviderManager as AudioProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * Video manager to validate the url matches an available provider.
   *
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $videoProvider;

  /**
   * Audio provider manager service.
   *
   * @var \Drupal\audio_embed_field\ProviderManager
   */
  protected $audioProvider;

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
      $container->get('plugin.manager.media_duplicate_validation'),
      $container->get('video_embed_field.provider_manager'),
      $container->get('audio_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, MessengerInterface $messenger, MediaDuplicateValidationManager $duplication_manager, VideoProviderManager $video_provider, AudioProviderManager $audio_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bundles, $current_user, $messenger, $duplication_manager);
    $this->videoProvider = $video_provider;
    $this->audioProvider = $audio_provider;
  }

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
      '#description' => $this->t('Enter the url to the sharable content. This will display as an embedded content on the page. Compatible providers are: %providers', ['%providers' => $this->getSharableProviderNames()]),
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
   * Get a list of all available embed code providers.
   *
   * @return string
   */
  protected function getSharableProviderNames() {
    $sharable_provider = [];

    $video_providers = $this->videoProvider->getProvidersOptionList();
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $provider */
    foreach ($video_providers as $provider_id => $provider) {
      $sharable_provider['Video'][0] = $this->t('Video:')->render();
      $sharable_provider['Video'][] = $provider->render();
    }

    $audio_providers = $this->audioProvider->getProvidersOptionList();
    foreach ($audio_providers as $provider_id => $provider) {
      $sharable_provider['Audio'][0] = $this->t('Audio:')->render();
      $sharable_provider['Audio'][] = $provider->render();
    }

    foreach ($sharable_provider as &$providers) {
      $providers = implode(', ', $providers);
    }

    return str_replace(':,',':', implode('; ', array_filter($sharable_provider)));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $value = trim($form_state->getValue('input'));
    $bundle = $this->bundleSuggestion->getBundleFromInput($value);
    if (!$bundle) {
      $form_state->setError($form['widget']['input'], $this->t('You must enter a URL or embed code.'));
    }
  }

}
