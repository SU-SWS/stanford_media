<?php

namespace Drupal\stanford_media;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * @file
 * Contains \Drupal\stanford_media\MediaInfo.
 */
class MediaFieldSave {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * MediaFieldSave constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, AccountProxyInterface $account_proxy) {
    $this->entityTypeManager = $entity_manager;
    $this->accountProxy = $account_proxy;
  }

  /**
   * Checks if a video already exists in the media browser.
   *
   * @param string $uri
   *
   * @return string|null
   */
  protected function videoExists($uri) {
    $select = Database::getConnection()
      ->select('media__field_media_video_embed_field', 've');
    $select->fields('ve', ['field_media_video_embed_field_value']);
    $select->condition('field_media_video_embed_field_value', $uri, '=');
    $results = $select->execute();
    return $results->fetchCol();
  }

  /**
   * Returns entity_id for a given uri.
   *
   * @param string $uri
   *
   * @return string|null
   */
  protected function getVideoTargetId($uri) {
    $select = Database::getConnection()
      ->select('media__field_media_video_embed_field', 've');
    $select->fields('ve', ['entity_id']);
    $select->condition('field_media_video_embed_field_value', $uri, '=');
    $results = $select->execute();
    return $results->fetchCol();
  }

  /**
   * Create a new media entity when a file_managed file is uploaded.
   *
   * @param array $element
   *   Managed file form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public function saveFile(array $element, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);

    if ($button_key == 'remove_button' || $form_state::hasAnyErrors()) {
      return;
    }

    if (!empty($element['#files'])) {
      foreach ($element['#files'] as $file) {
        if ($file instanceof File) {
          $media_bundle = 'file';

          // Switch the media bundle to image if there is a width attribute.
          // Can't find a better solution than that.
          if (isset($element['width'])) {
            $media_bundle = 'image';
          }

          // Load the media type entity to get the source field.
          $media_type = $this->entityTypeManager->getStorage('media_type')
            ->load($media_bundle);
          $source_field = $media_type->getSource()
            ->getConfiguration()['source_field'];

          // Check if a media entity has already been created.
          $query = $this->entityTypeManager->getStorage('media')->getQuery();
          $query->condition($source_field, $file->id());

          // Media entity already created.
          if (!empty($query->execute())) {
            continue;
          }

          // Create the new media entity.
          $media_entity = $this->entityTypeManager->getStorage('media')
            ->create([
              'bundle' => $media_type->id(),
              $source_field => $file,
              'uid' => $this->accountProxy->id(),
              'status' => TRUE,
              'type' => $media_type->getSource()->getPluginId(),
            ]);

          $source_field = $media_entity->getSource()
            ->getConfiguration()['source_field'];
          // If we don't save file at this point Media entity creates another file
          // entity with same uri for the thumbnail. That should probably be fixed
          // in Media entity, but this workaround should work for now.
          $media_entity->$source_field->entity->save();
          $media_entity->save();
        }
      }
    }
  }

  public function saveVideo(array $element, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);

    if ($button_key == 'remove_button' || $form_state::hasAnyErrors()) {
      return;
    }

    if ($uri = $form_state->getValue($element['#parents'])) {
      $uri = reset($uri);

      $media_bundle = 'video';

      // Check if video is already in media browser
      $video_data_uri = $this->videoExists($uri);

      // If the video doesn't already exist in the media browser, create it.
      if (empty($video_data_uri)) {
        // Load the media type entity to get the source field.
        $media_type = $this->entityTypeManager->getStorage('media_type')
          ->load($media_bundle);
        $source_field = $media_type->getSource()
          ->getConfiguration()['source_field'];

        $media_data = [
          'bundle' => $media_type->id(),
          $source_field => $uri,
          'uid' => $this->accountProxy->id(),
          'status' => TRUE,
          'type' => $media_type->getSource()->getPluginId(),
        ];

        if ($title = $form_state->getValue(['title', 0, 'value'])) {
          $media_data['name'] = $title;
        }

        // Create the new media entity.
        $media_entity = $this->entityTypeManager->getStorage('media')
          ->create($media_data);

        $media_entity->save();
      }
    }
  }
}
