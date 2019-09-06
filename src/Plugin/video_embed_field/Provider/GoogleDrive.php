<?php

namespace Drupal\stanford_media\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Google Drive Video provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "google_drive",
 *   title = @Translation("Google Drive")
 * )
 */
class GoogleDrive extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $embed_code = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'google_drive',
      '#url' => sprintf('https://drive.google.com/file/d/%s/preview', $this->getVideoId()),
      '#query' => [],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
      ],
    ];
    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    global $base_url;
    global $base_path;
    return $base_url . $base_path . $this->getModulePath() . '/img/google-drive.png';
  }

  /**
   * Get the path to this module.
   *
   * @return string
   *   Drupal relative path to module.
   *
   * @codeCoverageIgnore
   *   Ignore code coverage since the global function is not declared during
   *   unit tests.
   */
  protected function getModulePath() {
    return drupal_get_path('module', 'stanford_media');
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?(drive\.google\.com\/file\/d\/)(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
