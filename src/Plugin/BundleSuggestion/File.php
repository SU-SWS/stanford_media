<?php

namespace Drupal\stanford_media\Plugin\BundleSuggestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Upload file plugin suggestion.
 *
 * @BundleSuggestion (
 *   id = "file",
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class File extends BundleSuggestionBase {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFromString($input) {
    $valid_schemes = ['public', 'private', 'temporary'];
    // Only check for local files. Any url or external source is not applicable.
    if (!in_array($this->fileSystem->uriScheme($input), $valid_schemes)) {
      return NULL;
    }

    $extension = pathinfo($input, PATHINFO_EXTENSION);

    // Each media bundle should not have intersecting extensions allowed. Find
    // the first bundle that allows the given extension, and use that.
    foreach ($this->getMediaBundles() as $media_type) {
      if (in_array($extension, $this->getBundleExtensions($media_type)) !== FALSE) {
        return $media_type;
      }
    }
  }

  /**
   * Get all allowed file extensions that can be uploaded for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   Media type entity object.
   *
   * @return array|null
   *   All file extensions for the given media type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getBundleExtensions(MediaTypeInterface $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {

      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");

      // Explode the list of file extensions into a more friendly array.
      return explode(' ', $field->getSetting('file_extensions') ?: '');
    }
  }

}
