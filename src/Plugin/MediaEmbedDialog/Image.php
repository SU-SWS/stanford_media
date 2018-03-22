<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Element\Linkit;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\MediaEmbedDialogBase;

/**
 * Changes embedded image media items.
 *
 * @MediaEmbedDialog(
 *   id = "image",
 *   media_type = "image"
 * )
 */
class Image extends MediaEmbedDialogBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->bundle() == 'image';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return [
      'image_style' => NULL,
      'alt_text' => NULL,
      'title_text' => '',
    ];
  }

  /**
   * Use Linkit functions but replace the autocomplete library with our own.
   *
   * {@inheritdoc}
   *
   * @see Linkit::processLinkitAutocomplete()
   */
  public static function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    Linkit::processLinkitAutocomplete($element, $form_state, $complete_form);
    // Replace linkit autocomplete library with our own to fix some nasty bugs.
    $element['#attached']['library'] = ['stanford_media.autocomplete'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);

    $source_field = static::getMediaSourceField($this->entity);
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $image_field */
    $image_field = $this->entity->get($source_field);
    $default_alt = $image_field->getValue()[0]['alt'];

    $form['attributes'][$this->settingsKey]['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $this->getImageStyles(),
      '#default_value' => $input['image_style'],
      '#empty_option' => $this->t('None (original image)'),
    ];
    $form['attributes'][$this->settingsKey]['alt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative text'),
      '#description' => $this->t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
      '#default_value' => $input['alt_text'] ?: $default_alt,
    ];
    $this->buildLinkitField($form, $form_state);
    $form['attributes'][$this->settingsKey]['title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title Text'),
      '#description' => $this->t('Please describe wht the link above leads to. (ie. Stanford University Home Page'),
      '#default_value' => $input['title_text'],
      '#weight' => 199,
      '#states' => [
        'visible' => [
          'input[name*="[linkit][href]"]' => ['filled' => TRUE],
        ],
      ],
    ];

  }

  /**
   * Get all available image styles.
   *
   * @return array
   *   Keyed array of image styles and their labels.
   */
  protected function getImageStyles() {
    $styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple();
    $style_options = [];
    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      $style_options[$style->id()] = $style->label();
    }
    asort($style_options);
    return $style_options;
  }

  /**
   * Adds a linkit field to the form.
   *
   * @param array $form
   *   Embed dialog form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Embed dialog form state object.
   */
  protected function buildLinkitField(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
    $filter_format = $form_state->getBuildInfo()['args'][0];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->entityTypeManager->getStorage('editor')
      ->load($filter_format->id());
    $plugin_settings = $editor->getSettings()['plugins']['drupallink'];

    // Do not alter the form if Linkit is not enabled for this text format.
    if (!isset($plugin_settings['linkit_enabled']) || (isset($plugin_settings['linkit_enabled']) && !$plugin_settings['linkit_enabled'])) {
      return;
    }

    $input = [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      $display_settings = Json::decode($editor_object[$this->settingsKey]);
      $input = isset($display_settings['linkit']) ? $display_settings['linkit'] : [];
    }

    $linkit_profile_id = $editor->getSettings()['plugins']['drupallink']['linkit_profile'];

    $link_form = [
      '#title' => $this->t('Add Link to image'),
      '#type' => 'textfield',
      '#description' => $this->t('If you would like to make this image a link, enter the url here. Or start typing to find content.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#states' => [
        'visible' => [
          '[name="attributes[data-entity-embed-display-settings][image_link]"]' => ['value' => 'url'],
        ],
      ],
      '#process' => [
        [self::class, 'processLinkitAutocomplete'],
      ],
    ];

    $form['attributes'][$this->settingsKey]['linkit'] = [
      '#type' => 'container',
      '#weight' => 99.5,
    ];

    $fields = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];


    $form['attributes'][$this->settingsKey]['linkit']['href_dirty_check'] = [
      '#type' => 'hidden',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
    ];

    $form['attributes'][$this->settingsKey]['linkit']['href'] = $link_form;
    foreach ($fields as $field) {
      $form['attributes'][$this->settingsKey]['linkit'][$field] = [
        '#title' => $field,
        '#type' => 'hidden',
        '#default_value' => isset($input[$field]) ? $input[$field] : '',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function submitDialogForm(array &$form, FormStateInterface $form_state) {
    parent::submitDialogForm($form, $form_state);

    $settings = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
    ]);
    $settings = array_filter($settings);
    // Clean up the display settings, but we still want at least an empty alt
    // text. This also helps prevent an empty array which converts to an empty
    // string. An empty string breaks the render portion.
    $settings['alt_text'] = isset($settings['alt_text']) ? $settings['alt_text'] : '';
    $form_state->setValue([
      'attributes',
      'data-entity-embed-display-settings',
    ], $settings);

    $linkit_key = [
      'attributes',
      'data-entity-embed-display-settings',
      'linkit',
    ];
    $linkit_settings = $form_state->getValue($linkit_key);

    $href = $linkit_settings['href'];
    // No link: unset values to clean up embed code.
    if (!$href) {
      $form_state->unsetValue($linkit_key);
      return;
    }

    $href_dirty_check = $linkit_settings['href_dirty_check'];

    // Unset the attributes since this is an external url.
    if (!$href || $href !== $href_dirty_check) {
      unset($linkit_settings['data-entity-type']);
      unset($linkit_settings['data-entity-uuid']);
      unset($linkit_settings['data-entity-substitution']);
    }

    unset($linkit_settings['href_dirty_check']);

    $form_state->setValue($linkit_key, array_filter($linkit_settings));
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element) {
    $source_field = static::getMediaSourceField($element['#media']);

    if (!empty($element['#display_settings']['alt_text'])) {
      $element[$source_field][0]['#item_attributes']['alt'] = $element['#display_settings']['alt_text'];
    }

    if (!empty($element['#display_settings']['title_text'])) {
      $element[$source_field][0]['#item_attributes']['title'] = $element['#display_settings']['title_text'];
    }

    if (!empty($element['#display_settings']['linkit'])) {
      $element[$source_field][0]['#url'] = $element['#display_settings']['linkit']['href'];
      unset($element['#display_settings']['linkit']['href']);
      $element[$source_field][0]['#attributes'] = $element['#display_settings']['linkit'];
    }

    if (!empty($element['#display_settings']['image_style'])) {
      $element[$source_field]['#formatter'] = 'image';
      $element[$source_field][0]['#theme'] = 'image_formatter';;
      $element[$source_field][0]['#image_style'] = $element['#display_settings']['image_style'];
    }
    else {
      unset($element[$source_field][0]['#image_style']);
    }

    return $element;
  }

}
