<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Interface BundleSuggestionManagerInterface for plugin manager service.
 *
 * @package Drupal\stanford_media\Plugin
 */
interface BundleSuggestionManagerInterface extends PluginManagerInterface {

  /**
   * With a provided input string from the user, find an media bundle to match.
   *
   * @param string $input
   *   User entered data, such as url or file path.
   *
   * @return \Drupal\media\MediaTypeInterface|null
   *   Suggested bundle to match the input.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSuggestedBundle(string $input): ?MediaTypeInterface;

  /**
   * Get the suggested name of the media entity from the input value.
   *
   * @param string $input
   *   User input data such as a url.
   *
   * @return string|null
   *   Suggested name or null if none suggested.
   */
  public function getSuggestedName(string $input): ?string;

  /**
   * Get the available extension the user can upload.
   *
   * @return array
   *   All available extensions.
   */
  public function getAllExtensions(): array;

  /**
   * Get all media type bundles that are configured to have an upload field.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   Keyed array of media bundles with upload fields.
   */
  public function getUploadBundles(): array;

  /**
   * Get all allowed file extensions that can be uploaded for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   Media type entity object.
   *
   * @return string[]
   *   All file extensions for the given media type.
   */
  public function getBundleExtensions(MediaTypeInterface $media_type): array;

  /**
   * Get allowed extensions from the allowed media types.
   *
   * @param array $media_types
   *   Array of machine names of the allowed bundles.
   *
   * @return string[]
   *   Array of available file extensions.
   */
  public function getMultipleBundleExtensions(array $media_types): array;

  /**
   * Get the maximum file size for all media bundles.
   *
   * @param string[] $bundles
   *   Array of media type ids to limit, leave empty to get max for all bundles.
   *
   * @return int
   *   Maximum file size for all bundles.
   */
  public function getMaxFileSize(array $bundles = []): int;

  /**
   * Get maximum file size for the media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type bundle to get file size for.
   *
   * @return int
   *   The maximum file size.
   */
  public function getMaxFileSizeBundle(MediaTypeInterface $media_type): int;

  /**
   * Get the upload path for a specific media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   Media type to get path.
   *
   * @return string
   *   Upload path location.
   */
  public function getUploadPath(MediaTypeInterface $media_type): string;

}
