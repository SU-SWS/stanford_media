<?php

namespace Drupal\stanford_media\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\stanford_media\Form\MediaLibraryEmbeddableForm;
use Drupal\stanford_media\Form\MediaLibraryFileUploadForm;
use Drupal\stanford_media\Form\MediaLibraryGoogleFormForm;

/**
 * Entity type, field and plugin info hook implementations for stanford_media.
 */
class StanfordMediaEntityInfoHooks {

  /**
   * Hooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    // Add route for media entity type to view the usage details.
    $entity_types['media']->setLinkTemplate('usage', '/media/{media}/usage');
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type, $bundle): void {
    if ($entity_type->id() != 'media') {
      return;
    }

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $this->entityTypeManager->getStorage('media_type')
      ->load($bundle);
    if (!$media_type) {
      return;
    }

    $source = $media_type->getSource();

    // If the media type is using the embeddable plugin, add a constraint on the
    // unstructured code field.
    if ($source->getPluginId() == 'embeddable') {
      $unstructured_field = $source->getConfiguration()['unstructured_field_name'] ?? '';
      if (isset($fields[$unstructured_field])) {
        $fields[$unstructured_field]->addConstraint('embeddable');
      }
    }
  }

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    $sources['audio_file']['forms']['media_library_add'] = MediaLibraryFileUploadForm::class;
    $sources['file']['forms']['media_library_add'] = MediaLibraryFileUploadForm::class;
    $sources['image']['forms']['media_library_add'] = MediaLibraryFileUploadForm::class;
    $sources['video_file']['forms']['media_library_add'] = MediaLibraryFileUploadForm::class;
    $sources['google_form']['forms']['media_library_add'] = MediaLibraryGoogleFormForm::class;
    $sources['embeddable']['forms']['media_library_add'] = MediaLibraryEmbeddableForm::class;
  }

}
