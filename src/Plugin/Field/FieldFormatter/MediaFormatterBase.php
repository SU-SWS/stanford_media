<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\media\MediaInterface;

/**
 * Class MediaFormatter.
 *
 * @package Drupal\stanford_media\Plugin\Field\FieldFormatter
 */
abstract class MediaFormatterBase extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $is_applicable = parent::isApplicable($field_definition);
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');

    return $is_applicable && $target_type == 'media';
  }

  /**
   * Get the source field from the media type configuration.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Media entity.
   *
   * @return string
   *   Source field on the entity.
   */
  protected static function getSourceField(MediaInterface $entity): string {
    return $entity->getSource()->getConfiguration()['source_field'];
  }

  /**
   * Gets an array of view mode options.
   *
   * @param string $type
   *   The entity type whose view mode options should be returned.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   *
   * @codeCoverageIgnore
   *   This method is only used in the form building function and as that is not
   *   testable, this is not testable.
   */
  protected function getEntityDisplayModes(string $type = "media"): array {
    return $this->entityDisplayRepository->getViewModeOptions($type);
  }

}
