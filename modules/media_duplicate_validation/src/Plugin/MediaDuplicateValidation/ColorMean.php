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

  const THRESHOLD = 5;

  /**
   * {@inheritdoc}
   */
  public function isUnique($uri) {
    if (!($image_mean = $this->getColorMean($uri))) {
      return TRUE;
    }
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
    if (!($image_mean = $this->getColorMean($uri))) {
      return [];
    }

    $similar_media = [];

    foreach ($this->getExistingMeans() as $media_id => $mean) {
      $hammeringDistance = 0;
      for ($x = 0; $x < 64; $x++) {
        if ($image_mean[$x] != $mean[$x]) {
          $hammeringDistance++;
        }
      }
      if ($hammeringDistance < self::THRESHOLD) {
        $similar_media[$media_id] = Media::load($media_id);
      }
    }

    return array_filter($similar_media);
  }

  /**
   * Get existing media image data.
   *
   * @return array
   *   Keyed array of color mean data.
   */
  protected function getExistingMeans() {
    if (($cache = $this->cache->get($this->getCacheId())) && !empty($cache->data)) {
      return $cache->data;
    }
    $means = [];
    /** @var \Drupal\media\MediaInterface $media */
    foreach (Media::loadMultiple() as $media) {
      $fid = $media->getSource()->getSourceFieldValue($media);
      if ($file = File::load($fid)) {
        if ($color_mean = $this->getColorMean($file->getFileUri())) {
          $means[$media->id()] = $color_mean;
        }
      }
    }
    $this->cache->set($this->getCacheId(), $means);
    return $means;
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
  protected function getColorMean($uri) {
    $image = $this->createImage($uri);
    if (!$image) {
      return FALSE;
    }
    $image = $this->resizeImage($uri);
    imagefilter($image, IMG_FILTER_GRAYSCALE);
    $color_mean = $this->colorMeanValue($image);
    return $this->bits($color_mean);
  }

  /**
   * Returns array with mime type and if its jpg or png. Returns false if it
   * isn't jpg or png.
   *
   * @param string $path
   *   Path to image.
   *
   * @return array|bool
   */
  private function mimeType($path) {
    $mime = getimagesize($path);
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
  private function createImage($path) {
    $mime = $this->mimeType($path);

    if ($mime[2] == 'jpg') {
      return imagecreatefromjpeg($path);
    }
    else {
      if ($mime[2] == 'png') {
        return imagecreatefrompng($path);
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Resize the image to a 8x8 square and returns as image resource.
   *
   * @param string $path
   *   Path to image
   *
   * @return resource Image resource identifier
   */
  private function resizeImage($path) {
    $dimension = 50;
    $mime = $this->mimeType($path);
    $t = imagecreatetruecolor($dimension, $dimension);

    $source = $this->createImage($path);

    imagecopyresized($t, $source, 0, 0, 0, 0, $dimension, $dimension, $mime[0], $mime[1]);

    return $t;
  }

  /**
   * Returns the mean value of the colors and the list of all pixel's colors.
   *
   * @param resource $resource
   *   Image resource identifier
   *
   * @return array
   */
  private function colorMeanValue($resource) {
    $colorList = [];
    $colorSum = 0;
    for ($a = 0; $a < 8; $a++) {
      for ($b = 0; $b < 8; $b++) {
        $rgb = imagecolorat($resource, $a, $b);
        $colorList[] = $rgb & 0xFF;
        $colorSum += $rgb & 0xFF;
      }
    }

    return [$colorSum / 64, $colorList];
  }

  /**
   * Returns an array with 1 and zeros. If a color is bigger than the mean
   * value of colors it is 1
   *
   * @param array $colorMean
   *   Color Mean details.
   *
   * @return array
   */
  private function bits($colorMean) {
    $bits = [];
    foreach ($colorMean[1] as $color) {
      $bits[] = ($color >= $colorMean[0]) ? 1 : 0;
    }
    return $bits;
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSave(MediaInterface $entity) {
    $file = File::load($entity->getSource()->getSourceFieldValue($entity));
    if ($file) {
      $color_mean = $this->getColorMean($file->getFileUri());

      // Its not an image, we don't want to track it.
      if (!$color_mean) {
        return;
      }

      $means = [];
      if ($cache = $this->cache->get($this->getCacheId())) {
        $means = $cache->data;
      }
      $means[$entity->id()] = $color_mean;
      $this->cache->set($this->getCacheId(), $means);
    }
  }

}
