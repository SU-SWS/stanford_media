<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\media\Entity\MediaType;

/**
 * Interface BundleSuggestionManagerInterface for plugin manager service.
 *
 * @package Drupal\stanford_media\Plugin
 */
interface BundleSuggestionManagerInterface {

  /**
   * With a provided input string from the user, find an media bundle to match.
   *
   * @param string $input
   *   User entered data, such as a url or file path.
   *
   * @return \Drupal\media\Entity\MediaType|null
   *   Suggested bundle to match the input.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSuggestedBundle($input);

  /**
   * Get the suggested name of the media entity from the input value.
   *
   * @param string $input
   *   User input data such as a url.
   *
   * @return string|null
   *   Suggested name or null if none suggested.
   */
  public function getSuggestedName($input);

  /**
   * Get the available extension the user can upload.
   *
   * @return array
   *   All available extensions.
   */
  public function getAllExtensions();

  /**
   * Get all media type bundles that are configured to have an upload field.
   *
   * @return \Drupal\media\Entity\MediaType[]
   *   Keyed array of media bundles with upload fields.
   */
  public function getUploadBundles();

  /**
   * Get all allowed file extensions that can be uploaded for a media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type entity object.
   *
   * @return array
   *   All file extensions for the given media type.
   */
  public function getBundleExtensions(MediaType $media_type);

  /**
   * Get allowed extensions from the allowed media types.
   *
   * @param array $media_types
   *   Array of machine names of the allowed bundles.
   *
   * @return array
   *   Array of available file extensions.
   */
  public function getMultipleBundleExtensions(array $media_types);

  /**
   * Get the maximum file size for all media bundles.
   *
   * @return int
   *   Maximum file size for all bundles.
   */
  public function getMaxFilesize();

  /**
   * Get maximum file size for the media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   The media type bundle to get file size for.
   *
   * @return int
   *   The maximum file size.
   */
  public function getMaxFileSizeBundle(MediaType $media_type);

  /**
   * Get the upload path for a specific media type.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type to get path.
   *
   * @return string
   *   Upload path location.
   */
  public function getUploadPath(MediaType $media_type);

}
