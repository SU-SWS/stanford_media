<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;

/**
 * Field formatter for embeddables.
 *
 * @FieldFormatter (
 *   id = "embeddable_formatter",
 *   label = @Translation("Embeddable field formatter"),
 *   description = @Translation("Field formatter for Embeddable media."),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class EmbeddableFormatter extends FormatterBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media' || !$field_definition->getTargetBundle()) {
      return FALSE;
    }

    $media_type = MediaType::load($field_definition->getTargetBundle());
    return $media_type && $media_type->getSource() instanceof Embeddable;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $delta => $item) {
      $embed_markup = $item->getValue()['value'];
      if (!empty($embed_markup)){
        $elements[$delta] = [
          '#markup' => $item->getValue()['value'],
          '#allowed_tags' => [
            'iframe',
            'video',
            'source',
            'embed',
            'script',
          ],
          '#prefix' => '<div class="embeddable-content">',
          '#suffix' => '</div>',
        ];
      }
    }
    return $elements;
  }

}
