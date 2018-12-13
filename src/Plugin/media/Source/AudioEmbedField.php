<?php

namespace Drupal\stanford_media\Plugin\media\Source;

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
 *   description = @Translation("Enables audio_embed_field integration with media."),
 *   allowed_field_types = {"audio_embed_field"},
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
    if (!$url = $this->getAudioUrl($media)) {
      return FALSE;
    }
    $provider = $this->providerManager->loadProviderFromInput($url);
    $definition = $this->providerManager->loadDefinitionFromInput($url);
    switch ($name) {
      case 'default_name':
        if ($provider) {
          return $provider->getName();
        }
        return parent::getMetadata($media, 'default_name');

      case 'id':
        if ($provider) {
          return $provider->getIdFromInput($url);
        }
        return FALSE;

      case 'source':
      case 'source_name':
        if (!empty($definition)) {
          return $definition['id'];
        }
        return FALSE;

      case 'image_local':
      case 'image_local_uri':
        $thumbnail_uri = $this->getMetadata($media, 'thumbnail_uri');
        if (!empty($thumbnail_uri) && file_exists($thumbnail_uri)) {
          return $thumbnail_uri;
        }
        return parent::getMetadata($media, 'thumbnail_uri');

      case 'thumbnail_uri':
        if ($provider) {
          $provider->downloadThumbnail();
          $thumbnail_uri = $provider->getLocalThumbnailUri();
          if (!empty($thumbnail_uri)) {
            return $thumbnail_uri;
          }
        }
        return parent::getMetadata($media, 'thumbnail_uri');

    }
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
    $media_type = $this->entityTypeManager
      ->getStorage('media_type')
      ->load($media->bundle());
    $source_field = $this->getSourceFieldDefinition($media_type);
    $field_name = $source_field->getName();
    $audio_url = $media->{$field_name}->value;

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
