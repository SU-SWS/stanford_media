<?php

namespace Drupal\stanford_media\Plugin\BundleSuggestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AudioEmbed for audio_embed_field module suggestion.
 *
 * @BundleSuggestion (
 *   id = "audio_embed",
 *   field_types = {"audio_embed_field"}
 * )
 */
class AudioEmbed extends BundleSuggestionBase {

  /**
   * Get the audio provider manager service if it exists.
   *
   * @return \Drupal\audio_embed_field\ProviderManager|null
   *   Audio provider manager service if it exists.
   */
  protected static function getAudioProviderManager() {
    if (\Drupal::hasService('audio_embed_field.provider_manager')) {
      return \Drupal::service('audio_embed_field.provider_manager');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFromString($input) {
    $audio_provider = self::getAudioProviderManager();
    if (!$audio_provider) {
      return;
    }

    $audio_provider_def = $audio_provider->loadDefinitionFromInput($input);

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    foreach ($this->getMediaBundles() as $media_type) {
      $source_field = $media_type->getSource()
        ->getConfiguration()['source_field'];

      /** @var \Drupal\field\FieldConfigInterface $field */
      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");
      $allowed_providers = $field->getSetting('allowed_providers');

      if (
        $audio_provider_def &&
        $field->getType() == 'audio_embed_field' &&
        (empty($allowed_providers) || array_key_exists($audio_provider_def['id'], $allowed_providers))
      ) {
        return $media_type;
      }

    }
  }

}
