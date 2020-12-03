<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides media source plugin for Google Forms iframe.
 *
 * @MediaSource(
 *   id = "google_form",
 *   label = @Translation("Google Form"),
 *   description = @Translation("Embed a google form."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "generic.png"
 * )
 */
class GoogleForm extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * Key for "Form ID" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_ID = 'id';

  /**
   * The name of the height field.
   *
   * @var string
   */
  protected $heightField;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $configuration = $this->getConfiguration();
    $this->heightField = $configuration['height_field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'height_field_name' => 'field_media_google_form_hgt',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadataAttributes() {
    return [
      self::METADATA_ATTRIBUTE_ID => $this->t('Form ID'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case self::METADATA_ATTRIBUTE_ID:
        return $this->getId($media);

    }
    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritDoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'google_forms' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $height_options = $this->getHeightFieldOptions();
    $form['height_field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('iFrame Height'),
      '#default_value' => $this->configuration['height_field_name'],
      '#empty_option' => $this->t('- Choose -'),
      '#options' => $height_options,
      '#description' => $this->t('Select the field that will store the height of the iFrame.'),
      '#weight' => -99,
    ];
    return $form;
  }

  /**
   * Get the field options for the iframe height.
   *
   * @return string[]
   *   A list of field options for the media type form.
   */
  protected function getHeightFieldOptions() {
    $fields = $this->entityFieldManager->getFieldDefinitions('media', 'google_form');
    $options = [];
    foreach ($fields as $field_name => $field) {
      if ($field->getType() == 'integer') {
        $options[$field_name] = $field->getLabel();
      }
    }
    return $options;
  }

  /**
   * Get the form ID from the media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return string
   *   Google form id.
   */
  protected function getId(MediaInterface $media) {
    preg_match('/google.*forms\/([^ ]*)\/viewform/', $this->getSourceFieldValue($media), $form_id);
    return $form_id[1];
  }

  /**
   * Returns the name of the height field.
   *
   * @return string
   *   The name of the height field.
   */
  public function getHeightFieldName() {
    return $this->heightField;
  }

}
