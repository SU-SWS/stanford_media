<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\stanford_media\BundleSuggestion;
use Drupal\video_embed_field\ProviderManager;
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
      $container->get('video_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, MessengerInterface $messenger, ProviderManager $video_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bundles, $current_user, $messenger);
    $this->videoProvider = $video_provider;
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
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $providers = $this->videoProvider->getProvidersOptionList();
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $provider */
    foreach ($providers as &$provider) {
      $provider = $provider->render();
    }
    $providers = implode(', ', $providers);
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video Url'),
      '#description' => $this->t('Enter the url of the video. This will display as an embedded video on the page. Compatible providers are: %providers', ['%providers' => $providers]),
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
      $form_state->setError($form['widget']['input'], $this->t('You must enter a URL or embed code.'));
    }
  }

}
