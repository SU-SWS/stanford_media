<?php

namespace Drupal\stanford_media\Plugin\BundleSuggestion;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\media\MediaTypeInterface;

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
   * {@inheritdoc}
   */
  public function getBundleFromString(string $input): ?MediaTypeInterface {
    $valid_schemes = ['public', 'private', 'temporary'];
    // Only check for local files. Any url or external source is not applicable.
    if (!in_array(StreamWrapperManager::getScheme($input), $valid_schemes)) {
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
    return NULL;
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
  protected function getBundleExtensions(MediaTypeInterface $media_type): array {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {

      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");

      // Explode the list of file extensions into a more friendly array.
      return explode(' ', $field->getSetting('file_extensions') ?: '');
    }
    return [];
  }

}
