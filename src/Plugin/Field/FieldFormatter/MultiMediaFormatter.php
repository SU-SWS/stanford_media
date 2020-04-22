<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'multi media' formatter.
 *
 * @FieldFormatter(
 *   id = "media_multimedia_formatter",
 *   label = @Translation("Multiple Media Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MultiMediaFormatter extends MediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Open up the settings to see configuration options.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'image' => [
        'image_formatter' => 'image_style',
        'image_formatter_image_style' => 'large',
        'image_formatter_responsive_image_style' => 'full_responsive',
        'image_formatter_view_mode' => 'default',
      ],
      'video' => [
        'video_formatter' => 'entity',
        'video_formatter_view_mode' => 'default',
      ],
      'other' => [
        'view_mode' => 'default',
      ],
    ];

    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Can't test this with unit tests since the parent method has a global t()
   * function usage.
   *
   * @codeCoverageIgnore
   *   Ignore this because the parent method uses a global t() function and
   *   this form relies on the global function get_image_styles.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $vm = $elements['view_mode'];
    $vm['#title'] = $this->t('Default view mode for any other media type');
    unset($elements['view_mode']);

    // Set up vertical tabs.
    $elements['vt_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Multiple Media Formatter settings'),
      '#default_tab' => 'edit-image',
    ];
    // Donno why this is not working. Something to do with ajax perhaps.
    $elements['vt_settings']['#attached']['library'][] = 'core/drupal.vertical-tabs';

    $elements['image'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Settings'),
      '#group' => 'vt_settings',
      '#tree' => TRUE,
    ];

    $elements['video'] = [
      '#type' => 'details',
      '#title' => $this->t('Video Settings'),
      '#group' => 'vt_settings',
      '#tree' => TRUE,
    ];

    $elements['other'] = [
      '#type' => 'details',
      '#title' => $this->t('Other Settings'),
      '#group' => 'vt_settings',
      '#tree' => TRUE,
    ];

    // End VTabs.
    $elements['image']['image_formatter'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'image_style' => $this->t("Image Style"),
        'responsive_image_style' => $this->t("Responsive Image"),
        'entity' => $this->t("Rendered Entity"),
      ],
      '#title' => $this->t('Image Formatter'),
      '#default_value' => $this->getSetting('image')['image_formatter'] ?: '',
      '#attributes' => [
        'id' => 'image_formatter',
      ],
    ];

    $elements['image']['image_formatter_image_style'] = [
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->getSetting('image')['image_formatter_image_style'] ?: '',
      '#states' => [
        'visible' => [
          ':input[id="image_formatter"]' => ['value' => 'image_style'],
        ],
      ],
    ];

    $elements['image']['image_formatter_responsive_image_style'] = [
      '#type' => 'select',
      '#options' => $this->getResponsiveImageStyles(),
      '#title' => $this->t('Responsive Image Style'),
      '#default_value' => $this->getSetting('image')['image_formatter_responsive_image_style'],
      '#states' => [
        'visible' => [
          ':input[id="image_formatter"]' => ['value' => 'responsive_image_style'],
        ],
      ],
    ];

    $elements['image']['image_formatter_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getEntityDisplayModes(),
      '#title' => $this->t('Image View Mode'),
      '#description' => $this->t('Choose the view mode that the image styles will apply onto (if applicable).'),
      '#default_value' => $this->getSetting('image')['image_formatter_view_mode'] ?: '',
    ];

    $elements['video']['video_formatter'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'entity' => $this->t("Rendered Entity"),
      ],
      '#title' => $this->t('Video Formatter'),
      '#default_value' => $this->getSetting('video')['video_formatter'] ?: '',
    ];

    $elements['video']['video_formatter_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getEntityDisplayModes(),
      '#title' => $this->t('Video View Mode'),
      '#default_value' => $this->getSetting('video')['video_formatter_view_mode'],
    ];

    $elements['other']['view_mode'] = $vm;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Loop through each media type and try to find a render method.
    foreach ($elements as &$element) {
      /** @var \Drupal\media\MediaInterface $media_item */
      $media_item = $element['#media'];

      $source_id = $media_item->getSource()->getPluginId();
      $method_name = 'view' . ucfirst(strtolower($source_id)) . "Element";

      $vm_setting = $source_id . "_formatter_view_mode";
      $view_mode = (!empty($this->getSetting($source_id)[$vm_setting])) ? $this->getSetting($source_id)[$vm_setting] : 'default';
      $element['#view_mode'] = $view_mode;

      if (method_exists($this, $method_name)) {
        $this->{$method_name}($items, $element);
      }
    }

    return $elements;
  }

  /**
   * Use view modes to generate a render array.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   A list of EntityReferenceItems.
   * @param array $element
   *   A single render element.
   */
  protected function viewImageElement(FieldItemListInterface $items, array &$element) {
    $op = $this->getSetting('image')['image_formatter'] ?: '';
    if (!in_array($op, ['image_style', 'responsive_image_style'])) {
      return;
    }

    $element['#stanford_media_image_style'] = ($op == "image_style") ? $this->settings['image']['image_formatter_image_style'] : $this->settings['image']['image_formatter_responsive_image_style'];
    $element['#cache']['keys'][] = $element['#stanford_media_image_style'];

    if ($op == 'image_style') {
      $element['#pre_render'][] = [MediaImageFormatter::class, 'preRender'];
      return;
    }

    $element['#pre_render'][] = [MediaResponsiveImageFormatter::class, 'preRender'];

  }

  /**
   * Get available responsive image styles.
   *
   * @return array
   *   Keyed array of image styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @codeCoverageIgnore
   *  Ignore this because it is only used by the form and we can't test it.
   */
  protected function getResponsiveImageStyles() {
    $styles = $this->entityTypeManager->getStorage('responsive_image_style')
      ->loadMultiple();
    /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle $style */
    foreach ($styles as &$style) {
      $style = $style->label();
    }
    return $styles;
  }

}
