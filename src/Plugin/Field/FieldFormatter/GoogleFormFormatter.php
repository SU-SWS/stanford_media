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
    if ($field_definition->getTargetEntityTypeId() !== 'media' || !$field_definition->getTargetBundle()) {
      return FALSE;
    }

    $media_type = MediaType::load($field_definition->getTargetBundle());
    return $media_type && $media_type->getSource() instanceof GoogleForm;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $parent */
    $parent = $items->getParent();
    $iframe_title = $parent->getEntity()->label();
    $fields = $parent->getEntity()->getFields();
    if (!empty($fields['field_media_google_form_hgt'])) {
      $iframe_height = !empty($fields['field_media_google_form_hgt']->getValue()[0]['value']) ?
        $fields['field_media_google_form_hgt']->getValue()[0]['value'] : '600';
    }

    dpm($iframe_height);
    //$render_array['#attributes']['style'] = 'height: ' . $iframe_height . 'px;';

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $item) {
      $url = $item->getValue()['value'];
      $elements[] = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#value' => $this->t('Loading'),
        '#attributes' => [
          'src' => $url,
          'title' => $iframe_title,
          'class' => ['google-form'],
          'style' => 'height: ' . $iframe_height . 'px;',
        ],
      ];
    }
    $elements['#attached']['library'][] = 'stanford_media/google_forms';

    return $elements;
  }

}
