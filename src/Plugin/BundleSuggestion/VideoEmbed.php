<?php

namespace Drupal\stanford_media\Plugin\BundleSuggestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\video_embed_field\ProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Video.
 *
 * @BundleSuggestion (
 *   id = "video_embed",
 *   field_types = {"video_embed_field"}
 * )
 */
class VideoEmbed extends BundleSuggestionBase {

  /**
   * Video provider manager service.
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
      $container->get('entity_type.manager'),
      $container->get('video_embed_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,  EntityTypeManagerInterface $entity_type_manager, ProviderManager $video_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->videoProvider = $video_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFromString($input) {
    $video_provider_id = $this->videoProvider->loadProviderFromInput($input);
    if(!$video_provider_id){

    }

    foreach ($this->getMediaBundles() as $media_type) {
      $source_field = $media_type->getSource()
        ->getConfiguration()['source_field'];

      $field = $this->entityTypeManager->getStorage('field_config')->load("media.{$media_type->id()}.$source_field");

      if ($video_provider_id && $field->getType() == 'video_embed_field') {
        return $media_type;
      }
    }
  }

}
