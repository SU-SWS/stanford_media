<?php

namespace Drupal\stanford_media\Service;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Plugin\BundleSuggestionManager;

/**
 * Class BundleSuggestion.
 *
 * @package Drupal\stanford_media
 */
class BundleSuggestion {

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Bundle Suggestion plugin manager service.
   *
   * @var \Drupal\stanford_media\Plugin\BundleSuggestionManager
   */
  protected $bundleSuggesters;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * MediaHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\stanford_media\Plugin\BundleSuggestionManager $bundle_suggest_manager
   *   Bundle Suggestion plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, BundleSuggestionManager $bundle_suggest_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleSuggesters = $bundle_suggest_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get the available extension the user can upload.
   *
   * @return array
   *   All available extensions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllExtensions() {
    $media_types = $this->getUploadBundles();
    return $this->getMultipleBundleExtensions(array_keys($media_types));
  }

  /**
   * Get all media type bundles that are configured to have an upload field.
   *
   * @return \Drupal\media\Entity\MediaType[]
   *   Keyed array of media bundles with upload fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUploadBundles() {
    $upload_bundles = [];
    $media_types = $this->getMediaBundles();

    foreach ($media_types as $media_type) {
      $source_field = $media_type->getSource()
        ->getConfiguration()['source_field'];
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);

      if (!empty($field->getSetting('file_extensions'))) {
        $upload_bundles[$media_type->id()] = $media_type;
      }
    }

    return $upload_bundles;
  }

  /**
   * Get all allowed file extensions that can be uploaded for a media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type entity object.
   *
   * @return array
   *   All file extensions for the given media type.
   */
  public function getBundleExtensions(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      $extensions = $field->getSetting('file_extensions') ?: '';
      return array_filter(explode(' ', $extensions));
    }
    return [];
  }

  /**
   * Get allowed extensions from the allowed media types.
   *
   * @param array $media_types
   *   Array of machine names of the allowed bundles.
   *
   * @return array
   *   Array of available file extensions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMultipleBundleExtensions(array $media_types) {
    $media_types = $this->getMediaBundles($media_types);
    $extensions = [];
    foreach ($media_types as $media_type) {
      $extensions = array_merge($extensions, $this->getBundleExtensions($media_type));
    }
    return array_unique(array_filter($extensions));
  }

  /**
   * Get a suggested media type bundle as decided from the plugins.
   *
   * @param string $input
   *   The file uri.
   *
   * @return \Drupal\media\Entity\MediaType|null
   *   Media type bundle if one matches.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSuggestedBundle($input) {
    $bundle = $this->bundleSuggesters->getSuggestedBundle($input);
    $this->moduleHandler->alter('stanford_media_bundle_suggestion', $bundle, $input);
    return $bundle;
  }

  /**
   * Get the maximum file size for all media bundles.
   *
   * @return int
   *   Maximum file size for all bundles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMaxFilesize() {
    $max_filesize = Bytes::toInt(file_upload_max_size());
    $media_types = $this->getUploadBundles();

    foreach ($media_types as $media_type) {

      if ($max = $this->getMaxFileSizeBundle($media_type)) {
        if ($max > $max_filesize) {
          $max_filesize = $max;
        }
      }
    }

    return $max_filesize;
  }

  /**
   * Get maximum file size for the media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   The media type bundle to get file size for.
   *
   * @return int
   *   The maximum file size.
   */
  public function getMaxFileSizeBundle(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      return Bytes::toInt($field->getSetting('max_filesize'));
    }
    return 0;
  }

  /**
   * Get the upload path for a specific media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type to get path.
   *
   * @return string
   *   Upload path location.
   */
  public function getUploadPath(MediaType $media_type) {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];
    $path = 'public://';

    if ($source_field) {
      $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
      $path = 'public://' . $field->getSetting('file_directory');
    }

    // Ensure the path has a trailing slash.
    if (strrpos($path, '/') !== strlen($path)) {
      $path .= '/';
    }
    return $path;
  }

  /**
   * Get all or some media bundles.
   *
   * @param array $bundles
   *   Optionally specifiy which media bundles to load.
   *
   * @return \Drupal\media\Entity\MediaType[]
   *   Keyed array of all media types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMediaBundles(array $bundles = []) {
    return $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL);
  }

}
