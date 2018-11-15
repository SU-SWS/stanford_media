<?php

namespace Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationBase;

/**
 * Class ColorMean.
 *
 * @MediaDuplicateValidation(
 *   id = "color_mean"
 * )
 */
class ColorMean extends MediaDuplicateValidationBase {

  /**
   * Database table used for this plugin.
   */
  const DATABASE_TABLE = 'media_validate_color_mean';

  /**
   * Percent different that considers one pixel different from another.
   */
  const COLOR_THRESHOLD = 20;

  /**
   * Total percent of the image different threshold.
   */
  const THRESHOLD = 10;

  /**
   * The dimensions to resize the images to compare against.
   */
  const RESIZE_DIMENSION = 25;

  /**
   * Get the configured threshold that indicates images are similar.
   *
   * @return int
   *   Numerical difference of images.
   */
  public function getThreshold() {
    return self::THRESHOLD;
  }

  /**
   * {@inheritdoc}
   */
  public function getSimilarItems(MediaInterface $entity) {
    if (!($file = $this->getFile($entity, ['image']))) {
      return [];
    }
    $image_colors = $this->getColorData($file->getFileUri());

    $similar_media = [];

    /** @var \Drupal\media\Entity\Media $entity */
    foreach ($this->getCloseMedia($entity, $image_colors) as $similar_entity) {

      $similar_file = $this->getFile($similar_entity, ['image']);
      $file_likeness = $this->getLikeness($file->getFileUri(), $similar_file->getFileUri());

      // The percent likeness is within the threshold we have defined.
      if (100 - $file_likeness <= self::THRESHOLD) {

        // In case multiple images have the same likeness, change the percent
        // just a tiny bit so it can still be part of the set.
        while (isset($similar_media["$file_likeness"])) {
          $file_likeness -= .01;
        }

        // Cast the keys to strings so that they retain decimals values if
        // necessary.
        $similar_media["$file_likeness"] = $similar_entity;
      }
    }
    krsort($similar_media);
    return array_filter($similar_media);
  }

  /**
   * The percent likeness between to file uris.
   *
   * @param string $image_one
   *   Image Uri.
   * @param string $image_two
   *   Image Uri.
   *
   * @return float|int
   *   Percent of the image that is deemed similar.
   */
  protected function getLikeness($image_one, $image_two) {
    $image_one_colors = $this->getColorData($image_one);
    $image_two_colors = $this->getColorData($image_two);

    $different_pixels = 0;
    foreach ($image_one_colors as $row_number => $row) {
      foreach ($row as $column_number => $color_value) {

        $difference = abs($color_value - $image_two_colors[$row_number][$column_number]) / 265;
        if ((100 * $difference) > self::COLOR_THRESHOLD) {
          $different_pixels++;
        }
      }
    }

    $total_difference = 100 * ($different_pixels / pow(self::RESIZE_DIMENSION, 2));
    return 100 - $total_difference;
  }

  /**
   * Get a subset of all the images based on the column and row data.
   *
   * @param array $color_data
   *   Gray scale multi-dimension array of pixel information.
   *
   * @return array
   *   Array of media ids that are within the tolerance.
   */
  protected function getCloseMedia(MediaInterface $entity, array $color_data) {
    $averages = $this->getRowColumnAverages($color_data);
    $query = $this->database->select(self::DATABASE_TABLE, 't')
      ->fields('t', ['mid']);

    // Excludes the entity we are checking against.
    $query->condition('mid', $entity->id(), '<>');
    for ($i = 1; $i <= self::RESIZE_DIMENSION; $i++) {

      // Calculate the number of color values that are considered "similar"
      // given the percent threshold.
      $color_difference = 100 / 265 * self::COLOR_THRESHOLD;

      // Add conditions to the query for row and columns.
      $query->condition('column_' . $i, $averages['columns'][$i - 1] - $color_difference, '>=');
      $query->condition('column_' . $i, $averages['columns'][$i - 1] + $color_difference, '<=');

      $query->condition('row_' . $i, $averages['rows'][$i - 1] - $color_difference, '>=');
      $query->condition('row_' . $i, $averages['rows'][$i - 1] + $color_difference, '<=');
    }
    $result = $query->execute();

    $mids = [];
    while ($mid = $result->fetchField()) {
      $mids[] = $mid;
    }

    return Media::loadMultiple($mids);
  }

  /**
   * Get the array of data from the provided image URI.
   *
   * @param string $uri
   *   Image path.
   *
   * @return array|bool
   *   Array of color data or false if its not an image.
   */
  public function getColorData($uri) {
    static $color_data = [];
    if (isset($color_data[$uri])) {
      return $color_data[$uri];
    }

    $image = $this->createImage($uri);

    // File is not an jpg or png.
    if (!$image) {
      return FALSE;
    }

    $resized_image = $this->resizeImage($image, $this->mimeType($uri));
    imagefilter($resized_image, IMG_FILTER_GRAYSCALE);
    $color_data[$uri] = $this->getColorValues($resized_image);
    return $color_data[$uri];
  }

  /**
   * Returns array with mime type and if its jpg or png. Returns false if it
   * isn't jpg or png.
   *
   * @param string $path
   *   Path to image.
   *
   * @return array|bool
   *   Mime data or false if its not a jpg or png.
   */
  protected function mimeType($path) {
    $mime = getimagesize($path);
    if (!$mime) {
      return FALSE;
    }
    $return = [$mime[0], $mime[1]];

    switch ($mime['mime']) {
      case 'image/jpeg':
        $return[] = 'jpg';
        return $return;
      case 'image/png':
        $return[] = 'png';
        return $return;
      default:
        return FALSE;
    }
  }

  /**
   * Returns image resource or false if it's not jpg or png
   *
   * @param string $path
   *   Path to image
   *
   * @return bool|resource
   */
  protected function createImage($path) {
    $mime = $this->mimeType($path);
    switch ($mime[2]) {
      case 'jpg':
        return imagecreatefromjpeg($path);
      case 'png':
        return imagecreatefrompng($path);
    }
    return FALSE;
  }

  /**
   * Resize the image to a square and returns as image resource.
   *
   * @param resource $source
   *   Image resource
   * @param array $mime_data
   *   Array of mime type data.
   *
   * @return resource
   *   Resized source resource.
   */
  protected function resizeImage($source, array $mime_data) {
    $resized = imagecreatetruecolor(self::RESIZE_DIMENSION, self::RESIZE_DIMENSION);
    imagecopyresized($resized, $source, 0, 0, 0, 0, self::RESIZE_DIMENSION, self::RESIZE_DIMENSION, $mime_data[0], $mime_data[1]);
    return $resized;
  }

  /**
   * Returns the mean value of the colors and the list of all pixel's colors.
   *
   * @param resource $resource
   *   Image resource identifier
   *
   * @return array
   *   Array of data of the color information.
   */
  protected function getColorValues($resource) {
    $colorList = [];
    for ($a = 0; $a < self::RESIZE_DIMENSION; $a++) {
      for ($b = 0; $b < self::RESIZE_DIMENSION; $b++) {
        $rgb = imagecolorat($resource, $a, $b);
        $colorList[$a][$b] = $rgb & 0xFF;
      }
    }
    return $colorList;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity) {
    $file = File::load($entity->getSource()->getSourceFieldValue($entity));
    if ($file && ($color = $this->getColorData($file->getFileUri()))) {
      $fields = ['mid' => $entity->id()];

      $averages = $this->getRowColumnAverages($color);
      foreach ($averages['columns'] as $column_number => $value) {
        $fields['column_' . ($column_number + 1)] = $value;
      }
      foreach ($averages['rows'] as $row_number => $value) {
        $fields['row_' . ($row_number + 1)] = $value;
      }

      $this->database->merge(self::DATABASE_TABLE)
        ->fields($fields)
        ->key('mid', $entity->id())
        ->execute();
    }
  }

  /**
   * Get the average colors for each role and column for the given pixel data.
   *
   * @param array $color_data
   *   Gray scale multi-dimension array of pixel information.
   *
   * @return array
   *   Keyed array of average color values for each row and column.
   */
  protected function getRowColumnAverages(array $color_data) {
    $sums = [];

    foreach ($color_data as $row_number => $row) {
      foreach ($row as $column_number => $pixel_value) {
        if (!isset($sums['columns'][$column_number])) {
          $sums['columns'][$column_number] = 0;
        }
        $sums['columns'][$column_number] += $pixel_value;

        if (!isset($sums['rows'][$row_number])) {
          $sums['rows'][$row_number] = 0;
        }
        $sums['rows'][$row_number] += $pixel_value;
      }
    }

    foreach ($sums['columns'] as &$column_value) {
      $column_value = $column_value / self::RESIZE_DIMENSION;
    }
    foreach ($sums['rows'] as &$row_value) {
      $row_value = $row_value / self::RESIZE_DIMENSION;
    }
    return $sums;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaDelete(MediaInterface $entity) {
    $this->database->delete(self::DATABASE_TABLE)
      ->condition('mid', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    $schema[self::DATABASE_TABLE] = [
      'description' => 'Media validation information for color_mean plugin',
      'fields' => [
        'mid' => [
          'description' => 'The media entity ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['mid'],
    ];
    for ($i = 1; $i <= self::RESIZE_DIMENSION; $i++) {
      $schema[self::DATABASE_TABLE]['fields']['row_' . $i] = [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ];
      $schema[self::DATABASE_TABLE]['fields']['column_' . $i] = [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ];
    }
    return $schema;
  }

}
