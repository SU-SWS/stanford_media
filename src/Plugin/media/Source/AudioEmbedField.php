<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\audio_embed_field\ProviderPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\audio_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media source plugin for audio embed field.
 *
 * This file should be removed when
 * https://www.drupal.org/project/audio_embed_field/issues/2955601 is resolved.
 *
 * @MediaSource(
 *   id = "audio_embed_field",
 *   label = @Translation("Audio embed field"),
 *   description = @Translation("Enables audio_embed_field integration with
 *   media."), allowed_field_types = {"audio_embed_field"},
 *   default_thumbnail_filename = "audio.png"
 * )
 */
class AudioEmbedField extends MediaSourceBase {

  /**
   * The audio provider manager.
   *
   * @var \Drupal\audio_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The media settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mediaSettings;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   Config field type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\audio_embed_field\ProviderManagerInterface $provider_manager
   *   The audio provider manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, ProviderManagerInterface $provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->providerManager = $provider_manager;
    $this->mediaSettings = $config_factory->get('media.settings');
  }

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
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('audio_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_media_audio_embed_field',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $url = $this->getAudioUrl($media);
    if (!$url) {
      return FALSE;
    }
    $provider = $this->providerManager->loadProviderFromInput($url);
    $definition = $this->providerManager->loadDefinitionFromInput($url);

    if ($function = $this->getMetaDataFunction($name)) {
      return call_user_func([$this, $function], $media, $provider);
    }

    switch ($name) {
      case 'id':
        return $provider->getIdFromInput($url) ?: FALSE;

      case 'source':
      case 'source_name':
        return $definition['id'];
    }
    return parent::getMetadata($media, $name);
  }

  /**
   * Get provider name for the media element.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   * @param \Drupal\audio_embed_field\ProviderPluginInterface $provider
   *   Video provider.
   *
   * @return string
   *   Name of audio provider.
   */
  protected function getMetaDataDefaultName(MediaInterface $media, ProviderPluginInterface $provider) {
    return $provider->getName();
  }

  /**
   * Get a separate function if one exists for the given meta data name.
   *
   * @param string $name
   *   Meta data name.
   *
   * @return string|null
   *   Method name or null if no method exists.
   */
  protected function getMetaDataFunction($name) {
    // Non-alpha and non-numeric characters become spaces
    $function = preg_replace('/[^a-z0-9]+/i', ' ', $name);
    $function = ucwords(trim($function));
    $function = str_replace(" ", "", $function);
    $function = "getMetaData$function";
    if (method_exists($this, $function)) {
      return $function;
    }
  }

  /**
   * Get thumbnail uri for the media element.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   * @param \Drupal\audio_embed_field\ProviderPluginInterface $provider
   *   Video provider.
   *
   * @return string|null
   *   Uri of thumbnail image.
   */
  protected function getMetaDataThumbnailUri(MediaInterface $media, ProviderPluginInterface $provider) {
    if ($provider) {
      $provider->downloadThumbnail();
      $thumbnail_uri = $provider->getLocalThumbnailUri();
      if (!empty($thumbnail_uri)) {
        return $thumbnail_uri;
      }
    }
    return parent::getMetadata($media, 'thumbnail_uri');
  }

  /**
   * Get local thumbnail image.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return string|null
   *   Path to local thumbnail image.
   */
  protected function getMetaDataImageLocal(MediaInterface $media) {
    $thumbnail_uri = $this->getMetadata($media, 'thumbnail_uri');
    if (!empty($thumbnail_uri) && file_exists($thumbnail_uri)) {
      return $thumbnail_uri;
    }
    return parent::getMetadata($media, 'thumbnail_uri');
  }

  /**
   * Get local thumbnail image.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return string|null
   *   Path to local thumbnail image.
   */
  protected function getMetaDataImageLocalUri(MediaInterface $media) {
    return $this->getMetaDataImageLocal($media);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'id' => $this->t('Audio ID.'),
      'source' => $this->t('Audio source machine name.'),
      'source_name' => $this->t('Audio source human name.'),
      'image_local' => $this->t('Copies thumbnail image to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
    ];
  }

  /**
   * Get the audio URL from a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   A audio URL or FALSE on failure.
   */
  protected function getAudioUrl(MediaInterface $media) {
    $source_field = $media->getSource()->getConfiguration()['source_field'];
    $audio_url = $media->{$source_field}->value;
    return isset($audio_url) ? $audio_url : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Audio Url');
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldDefinition(MediaTypeInterface $type) {
    $field = !empty($this->configuration['source_field']) ? $this->configuration['source_field'] : 'field_media_audio_embed_field';
    if ($field) {
      // Be sure that the suggested source field actually exists.
      $fields = $this->entityFieldManager->getFieldDefinitions('media', $type->id());
      return isset($fields[$field]) ? $fields[$field] : NULL;
    }
    return NULL;
  }

}
