<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_media\Plugin\media\Source\GoogleForm;

/**
 * Field formatter for google form iframes.
 *
 * @FieldFormatter (
 *   id = "google_form_formatter",
 *   label = @Translation("Google Form iFrame"),
 *   description = @Translation("Apply an image style to image media items."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GoogleFormFormatter extends FormatterBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $media_type = $field_definition->getTargetBundle();

      if ($media_type) {
        $media_type = MediaType::load($media_type);
        return $media_type && $media_type->getSource() instanceof GoogleForm;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $item) {
      $url = $item->getValue()['value'];
      $elements[] = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#value' => $this->t('Loading'),
        '#attributes' => ['src' => $url, 'class' => ['google-form']],
      ];
    }
    $elements['#attached']['library'][] = 'stanford_media/google_forms';

    return $elements;
  }

}
