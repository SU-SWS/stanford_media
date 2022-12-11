<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Token;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\MediaInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\stanford_media\Plugin\EmbedValidatorPluginManager;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Embeddable Media Source plugin.
 *
 * @MediaSource(
 *   id = "embeddable",
 *   label = @Translation("Stanford Embedded Media"),
 *   description = @Translation("Embeds a third-party resource."),
 *   default_thumbnail_filename = "generic.png",
 *   providers = {"ArcGIS StoryMaps", "CircuitLab", "Codepen", "Dailymotion",
 *   "Facebook", "Flickr", "Getty Images", "Instagram", "Issuu", "Livestream",
 *   "MathEmbed", "SimpleCast", "SlideShare", "SoundCloud", "Spotify",
 *   "Stanford Digital Repository", "Twitter"}, allowed_field_types =
 *   {"string", "string_long"},
 * )
 */
class Embeddable extends OEmbed implements EmbeddableInterface {

  /**
   * Embed validation plugin manager.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidatorPluginManager
   */
  protected $embedValidation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('logger.factory')->get('media'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('plugin.manager.embed_validator_plugin_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, FileSystemInterface $file_system, Token $token, EmbedValidatorPluginManager $embed_validation = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory, $field_type_manager, $logger, $messenger, $http_client, $resource_fetcher, $url_resolver, $iframe_url_helper, $file_system, $token);
    $this->embedValidation = $embed_validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = [
      'unstructured_field_name' => 'field_media_embeddable_code',
      'embed_validation' => [],
    ];
    return $default_config + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = $this->getSourceFieldOptions();
    $form['unstructured_field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field for unstructured embed codes'),
      '#default_value' => $this->configuration['unstructured_field_name'],
      '#empty_option' => $this->t('- Choose -'),
      '#options' => $options,
      '#description' => $this->t('Select the field that will store essential information about the media item.'),
      '#weight' => -99,
    ];
    $embed_validation_plugins = [];
    foreach ($this->embedValidation->getDefinitions() as $definition) {
      $embed_validation_plugins[$definition['id']] = $definition['label'];
    }
    $form['embed_validation'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Embeds Validation'),
      '#description' => $this->t("Validate the entered embed code for users who don't have the 'Bypass Embed Code Field Validation' permission. Leaving this empty will enable all validators."),
      '#options' => $embed_validation_plugins,
      '#default_value' => $this->configuration['embed_validation'],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    if ($this->hasUnstructured($media)) {
      return $this->getUnstructuredMetadata($media, $attribute_name);
    }
    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * Gets the value for a metadata attribute for a given media item.
   *
   * This is an alternate version to account for unstructured embeds.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   * @param string $name
   *   Name of the attribute to fetch.
   *
   * @return mixed|null
   *   Metadata attribute value or NULL if unavailable.
   */
  protected function getUnstructuredMetadata(MediaInterface $media, $name) {
    switch ($name) {
      case 'title':
        return $media->label();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function hasUnstructured(MediaInterface $media): bool {
    return (
      !$media->get($this->configuration['unstructured_field_name'])
        ->isEmpty() &&
      $media->get($this->configuration['source_field'])->isEmpty()
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getSourceFieldConstraints() {
    return ['embeddable' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldValue(MediaInterface $media) {
    $source_field = $this->hasUnstructured($media) ? $this->configuration['unstructured_field_name'] : $this->configuration['source_field'];
    if (empty($source_field)) {
      throw new \RuntimeException('Source field for media source is not defined.');
    }

    $items = $media->get($source_field);
    if ($items->isEmpty()) {
      return NULL;
    }

    $field_item = $items->first();
    return $field_item->{$field_item->mainPropertyName()};
  }

  /**
   * {@inheritdoc}
   */
  public function embedCodeIsAllowed($code): bool {
    $plugins = $this->getEnabledValidationPlugins();
    foreach ($plugins as $plugin) {
      if ($plugin->isEmbedCodeAllowed($code)) {
        return TRUE;
      }
    }
    return empty($plugins);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEmbedCode($code): string {
    foreach ($this->getEnabledValidationPlugins() as $plugin) {
      if ($plugin->isEmbedCodeAllowed($code)) {
        return $plugin->prepareEmbedCode($code);
      }
    }
    return $code;
  }

  /**
   * Get the enabled embed validation plugins.
   *
   * @return \Drupal\stanford_media\Plugin\EmbedValidatorInterface[]
   *   Keyed array of plugins.
   */
  protected function getEnabledValidationPlugins(): array {
    $plugins = [];
    $plugin_ids = $this->configuration['embed_validation'] ?? array_keys($this->getPluginDefinition());
    foreach ($plugin_ids as $plugin_id) {
      /** @var \Drupal\stanford_media\Plugin\EmbedValidatorInterface $plugin */
      try {
        $plugins[$plugin_id] = $this->embedValidation->createInstance($plugin_id);
      }
      catch (\Exception $e) {
        // The plugin didn't exist, log in and move on.
        $this->logger->error($this->t('Unable to create embed validation plugin @plugin_id. @message'), [
          '@plugin_id' => $plugin_id,
          '@message' => $e->getMessage(),
        ]);
      }
    }
    return $plugins;
  }

}
