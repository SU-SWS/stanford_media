<?php

namespace Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidation;

use Drupal\media\MediaInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationBase;

/**
 * Compares an image to other images.
 *
 * To do this we resize all images to a 25x25 grayscale image. Then comparing
 * the average color value of each row and column we collect a subset of similar
 * images. Then we compare pixel by pixel and with the thresholds we can mark
 * each one as similar or not.
 *
 * @MediaDuplicateValidation(
 *   id = "color_mean"
 * )
 */
class ColorMean extends MediaDuplicateValidationBase {

  /**
   * Database table used for this plugin.
   */
  const DATABASE_TABLE = self::DATABASE_PREFIX . 'color_mean';

  /**
   * Percent different each color can be to be considered the same.
   */
  const PIXEL_TOLLERANCE = 25;

  /**
   * Total percent that is different but still considered similar.
   */
  const IMAGE_TOLLERANCE = 10;

  /**
   * The dimensions to resize the images to compare against.
   */
  const RESIZE_DIMENSION = 25;

  /**
   * Keyed array of image color data with the image uri as the key.
   *
   * @var array
   */
  protected $imageColors = [];

  /**
   * {@inheritdoc}
   */
  public function getSimilarItems(MediaInterface $entity): array {
    if (!($file = $this->getFile($entity, ['image']))) {
      return [];
    }

    // The file failed because its either not an image or its a gif.
    if (!($image_colors = $this->getColorData($file->getFileUri()))) {
      return [];
    }

    $similar_media = [];

    /** @var \Drupal\media\Entity\Media $entity */
    // After finding all the media entities that might be close enough to be
    // considered similar, we'll find which ones are within the similarity
    // tolerance. Then based on the percent of similarity, create an array with
    // the similarity as the key.
    foreach ($this->getCloseMedia($entity, $image_colors) as $similar_entity) {

      $similar_file = $this->getFile($similar_entity, ['image']);
      $file_likeness = $this->getLikeness($file->getFileUri(), $similar_file->getFileUri());

      // The percent likeness is within the threshold we have defined.
      if (100 - $file_likeness <= self::IMAGE_TOLLERANCE) {

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

    // Reverse sort by the keys to put the most relevant at the front.
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
  protected function getLikeness($image_one, $image_two): float|int {
    $image_one_colors = $this->getColorData($image_one);
    $image_two_colors = $this->getColorData($image_two);

    $different_pixels = 0;

    // Go pixel by pixel and find how many different pixels the two images have.
    foreach ($image_one_colors as $row_number => $row) {
      foreach ($row as $column_number => $color_value) {

        // Each pixel can be between 0 and 255. Use 256 to include 0 as a
        // possible value.
        $difference = abs($color_value - $image_two_colors[$row_number][$column_number]) / 256;

        // Percent difference is greater than the allowed tollerance, its
        // different.
        if ((100 * $difference) > self::PIXEL_TOLLERANCE) {
          $different_pixels++;
        }
      }
    }

    // Calculate the percent of the image that is different.
    $total_pixels = pow(self::RESIZE_DIMENSION, 2);
    return 100 * (($total_pixels - $different_pixels) / $total_pixels);
  }

  /**
   * Get a subset of all the images based on the column and row data.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity we are comparing.
   * @param array $color_data
   *   Gray scale multi-dimension array of pixel information.
   *
   * @return \Drupal\media\MediaInterface[]
   *   Array of media ids that are within the tolerance.
   */
  protected function getCloseMedia(MediaInterface $entity, array $color_data): array {
    $averages = $this->getRowColumnAverages($color_data);
    $query = $this->database->select(self::DATABASE_TABLE, 't')
      ->fields('t', ['mid']);

    // If the media entity hasn't been saved yet, it wont have an ID. But we
    // want to exclude the entity we are checking against.
    if ($entity->id()) {
      $query->condition('mid', $entity->id(), '<>');
    }

    for ($i = 1; $i <= self::RESIZE_DIMENSION; $i++) {

      // Calculate the number of color values that are considered "similar"
      // given the percent threshold.
      $color_difference = 100 / 255 * self::PIXEL_TOLLERANCE;

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
    if (empty($mids)) {
      return [];
    }

    // Load the media ids that match the bundle from the compared media item.
    $mids = $this->entityTypeManager->getStorage('media')
      ->getQuery()
      ->accessCheck()
      ->condition('bundle', $entity->bundle())
      ->condition('mid', $mids, 'IN')
      ->execute();
    return $this->entityTypeManager->getStorage('media')->loadMultiple($mids);
  }

  /**
   * Get the array of data from the provided image URI.
   *
   * @param string $uri
   *   Image path.
   *
   * @return array|bool
   *   Array of color data or false if it's not an image.
   */
  public function getColorData($uri): array|bool {
    if (isset($this->imageColors[$uri])) {
      // We've already gotten the data for this URI, lets use that.
      return $this->imageColors[$uri];
    }

    // If the file is not a jpg or png we'll skip it.
    if (!($image = $this->createImage($uri))) {
      return FALSE;
    }

    // Resize and greyscale the image first.
    $resized_image = $this->resizeImage($image, $this->mimeType($uri));
    imagefilter($resized_image, IMG_FILTER_GRAYSCALE);
    $this->imageColors[$uri] = $this->getColorValues($resized_image);
    return $this->imageColors[$uri];
  }

  /**
   * Getmime type information if its jpg or png.
   *
   * @param string $path
   *   Path to image.
   *
   * @return array|bool
   *   Mime data or false if it's not a jpg or png.
   */
  protected function mimeType($path): array|bool {
    $mime = @getimagesize($path);
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
    }
    return FALSE;
  }

  /**
   * Create an image resource from a given file path.
   *
   * @param string $path
   *   Path to image.
   *
   * @return bool|resource
   *   Image resource or false if not an image.
   */
  protected function createImage($path) {
    $mime = $this->mimeType($path);
    if (!$mime) {
      // Unable to detect the mime type, so lets just build a fake array that
      // Will still pass through the switch below.
      $mime = [FALSE, FALSE, FALSE];
    }

    switch ($mime[2]) {
      case 'jpg':
        return imagecreatefromjpeg($path);

      case 'png':
        return imagecreatefrompng($path);
    }
    $this->logger->info('Unable to create image from @path. Path is not an jpg or png', ['@path' => $path]);
    return FALSE;
  }

  /**
   * Resize the image to a square and returns as image resource.
   *
   * @param resource $source
   *   Image resource.
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
   *   Image resource.
   *
   * @return array
   *   Array of data of the color information.
   */
  protected function getColorValues($resource): array {
    $colorList = [];
    for ($a = 0; $a < self::RESIZE_DIMENSION; $a++) {
      for ($b = 0; $b < self::RESIZE_DIMENSION; $b++) {
        // Find the color value at each pixel.
        $rgb = imagecolorat($resource, $a, $b);
        $colorList[$a][$b] = $rgb & 0xFF;
      }
    }
    return $colorList;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity): void {
    parent::mediaSave($entity);
    $file = $this->getFile($entity);

    // Populate our data table with the column and row data for fast loopup
    // later.
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
  protected function getRowColumnAverages(array $color_data): array {
    $sums = [
      'columns' => array_fill(0, self::RESIZE_DIMENSION, 0),
      'rows' => array_fill(0, self::RESIZE_DIMENSION, 0),
    ];

    // Sum up the color values in each row and in each column.
    foreach ($color_data as $row_number => $row) {
      foreach ($row as $column_number => $pixel_value) {
        $sums['columns'][$column_number] += $pixel_value;
        $sums['rows'][$row_number] += $pixel_value;
      }
    }

    // Calculate the average color for for each row and column.
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
  public function mediaDelete(MediaInterface $entity): void {
    parent::mediaDelete($entity);
    // Remove the data from the database.
    $this->database->delete(self::DATABASE_TABLE)
      ->condition('mid', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function schema(): array {
    $schema = parent::schema();
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

    // Create "Rows" and "Columns" as fields within the table.
    for ($i = 1; $i <= self::RESIZE_DIMENSION; $i++) {
      $field_schema = [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ];
      $schema[self::DATABASE_TABLE]['fields']['row_' . $i] = $field_schema;
      $schema[self::DATABASE_TABLE]['fields']['column_' . $i] = $field_schema;
    }
    return $schema;
  }

}
