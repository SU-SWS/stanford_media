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
   * The name of the iframe height field.
   *
   * @var string
   */
  protected $iframeHeightField;


  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $media_type = MediaType::load($field_definition->getTargetBundle());
    $this->iframeHeightField = $media_type->getSource()->getConfiguration()['height_field_name'];
  }

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

    if (!empty($fields[$this->iframeHeightField])) {
      $iframe_height = !empty($fields[$this->iframeHeightField]->getValue()[0]['value']) ?
        $fields[$this->iframeHeightField]->getValue()[0]['value'] : '600';
    }

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
