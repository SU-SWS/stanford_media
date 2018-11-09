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
   * {@inheritdoc}
   */
  public function isUnique($uri) {
    return empty($this->getSimilarItems($uri));
  }

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
  public function getSimilarItems($uri) {
    if (!($image_colors = $this->getColorMean($uri))) {
      return [];
    }

    $similar_media = [];

    $query = $this->database->select(self::DATABASE_TABLE, 't')
      ->fields('t')
      ->execute();

    foreach ($query->fetchAllKeyed() as $mid => $data) {
      $data = unserialize($data);

      $different_pixels = 0;
      foreach ($data as $x_position => $row) {
        foreach ($row as $y_position => $color_value) {
          $difference = abs($color_value - $image_colors[$x_position][$y_position]) / 265;
          if ((100 * $difference) > self::COLOR_THRESHOLD) {
            $different_pixels++;
          }
        }
      }

      $total_difference = 100 * ($different_pixels / (count($data) * count($data[0])));
      if ($total_difference <= $this->getThreshold()) {
        $likeness = 100 - $total_difference;

        while (isset($similar_media["$likeness"])) {
          $likeness -= .01;
        }

        $similar_media["$likeness"] = Media::load($mid);
      }
    }
    krsort($similar_media);
    return array_filter($similar_media);
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
  public static function getColorMean($uri) {
    $image = self::createImage($uri);

    // File is not an jpg or png.
    if (!$image) {
      return FALSE;
    }

    $resized_image = self::resizeImage($image, self::mimeType($uri));
    imagefilter($resized_image, IMG_FILTER_GRAYSCALE);
    return self::getColorValues($resized_image);
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
  protected static function mimeType($path) {
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
  protected static function createImage($path) {
    $mime = self::mimeType($path);
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
  protected static function resizeImage($source, array $mime_data) {
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
  protected static function getColorValues($resource) {
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
    if ($file && ($color_mean = self::getColorMean($file->getFileUri()))) {
      $this->database->merge(self::DATABASE_TABLE)
        ->fields([
          'mid' => $entity->id(),
          'color_mean' => serialize($color_mean),
        ])
        ->key('mid', $entity->id())
        ->execute();
    }
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
        'color_mean' => [
          'description' => 'The color mean data of the media file.',
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
      'primary key' => ['mid'],
    ];
    return $schema;
  }

}
