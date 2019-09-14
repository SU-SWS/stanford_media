<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Utility\Html;
use Drupal\media\MediaInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin class for video plugins.
 *
 * @package Drupal\stanford_media\Plugin\MediaEmbedDialog
 */
abstract class VideoEmbedBase extends MediaEmbedDialogBase {

  /**
   * Video manager to validate the url matches an available provider.
   *
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $videoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [
      'autoplay' => 0,
      'loop' => 0,
      'class' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface && $this->entity->bundle() == 'video') {
      $source_field = static::getMediaSourceField($this->entity);
      $url = $this->entity->get($source_field)->getValue()[0]['value'];
      $provider = $this->videoManager->loadProviderFromInput($url);

      if ($provider->getPluginId() == $this->getPluginDefinition()['video_provider']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    $field = static::getMediaSourceField($element['#media']);
    // Add the class to the container instead of the iframe.
    if (!empty($element['#display_settings']['class'])) {
      foreach (explode(' ', $element['#display_settings']['class']) as $class) {
        $element[$field][0]['#attributes']['class'][] = Html::cleanCssIdentifier($class);
      }
    }
    return $element;
  }

}
