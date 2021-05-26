<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Plugin\media\Source\OEmbed;

/**
 * Embeddable Media Source plugin.
 *
 * @MediaSource(
 *   id = "embeddable",
 *   label = @Translation("Stanford Embedded Media"),
 *   description = @Translation("Embeds a third-party resource."),
 *   default_thumbnail_filename = "generic.png",
 *   providers = {"ArcGIS StoryMaps", "CircuitLab", "Codepen", "Dailymotion", "Facebook", "Flickr", "Getty Images", "Instagram", "Issuu", "Livestream", "MathEmbed", "Simplecast", "SlideShare", "SoundCloud", "Spotify", "Stanford Digital Repository", "Twitter"},
 *   allowed_field_types = {"string", "string_long"},
 * )
 */
class Embeddable extends OEmbed implements EmbeddableInterface {

  /**
   * The name of the oEmbed field.
   *
   * @var string
   */
  protected $oEmbedField;

  /**
   * The name of the Unstructured field.
   *
   * @var string
   */
  protected $unstructuredField;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, FileSystemInterface $file_system = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory, $field_type_manager, $logger, $messenger, $http_client, $resource_fetcher, $url_resolver, $iframe_url_helper, $file_system);
    $configuration = $this->getConfiguration();
    $this->oEmbedField = $configuration['source_field'];
    $this->unstructuredField = $configuration['unstructured_field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'unstructured_field_name' => 'field_media_embeddable_code',
    ] + parent::defaultConfiguration();
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
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    if ($this->hasUnstructured($media)) {
      return $this->getUnstructuredMetadata($media, $name);
    }
    return parent::getMetadata($media, $name);
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
  public function hasUnstructured(MediaInterface $media) {
    return !$media->get($this->unstructuredField)->isEmpty() && $media->get($this->oEmbedField)->isEmpty();
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
    $source_field = $this->hasUnstructured($media) ? $this->unstructuredField : $this->oEmbedField;
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

}
