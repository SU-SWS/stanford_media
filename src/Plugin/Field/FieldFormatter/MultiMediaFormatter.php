<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaFormatterBase;
use Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaImageFormatter;
use Drupal\stanford_media\Plugin\Field\FieldFormatter\MediaResponsiveImageFormatter;

/**
 * Plugin implementation of the 'multi media' formatter.
 *
 * @FieldFormatter(
 *   id = "multimedia",
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
      'image' => null,
      'video' => null,
      'audio' => null,
      'other' => null,
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
   *   Ignore this because the parent method uses a global t() function.
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

    $elements['audio'] = [
      '#type' => 'details',
      '#title' => $this->t('Audio Settings'),
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
        'responsive_image' => $this->t("Responsive Image"),
        'entity' => $this->t("Rendered Entity"),
      ],
      '#title' => $this->t('Image Formatter'),
      '#default_value' => $this->getSetting('image')['image_formatter'],
      '#attributes' => [
        'id' => 'image_formatter',
      ],
    ];

    $elements['image']['image_formatter_image_style'] = [
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->getSetting('image')['image_formatter_image_style'],
      '#states' => array(
        'visible' => array(
          ':input[id="image_formatter"]' => array('value' => 'image_style'),
        ),
      ),
    ];

    $elements['image']['image_formatter_responsive_image_style'] = [
      '#type' => 'select',
      '#options' => $this->getResponsiveImageStyles(),
      '#title' => $this->t('Responsive Image Style'),
      '#default_value' => $this->getSetting('image')['image_formatter_responsive_image_style'],
      '#states' => array(
        'visible' => array(
          ':input[id="image_formatter"]' => array('value' => 'responsive_image'),
        ),
      ),
    ];

    $elements['image']['image_formatter_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link Media to Parent'),
      '#default_value' => $this->getSetting('image')['image_formatter_link'],
      '#states' => array(
        'visible' => array(
          ':input[id="image_formatter"]' => array('value' => 'image_style'),
        ),
      ),
    ];

    $elements['image']['image_formatter_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getEntityDisplayModes(),
      '#title' => $this->t('Image View Mode'),
      '#default_value' => $this->getSetting('image')['image_formatter_view_mode'],
      '#states' => array(
        'visible' => array(
          ':input[id="image_formatter"]' => array('value' => 'entity'),
        ),
      ),
    ];

    $elements['video']['video_formatter'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'entity' => $this->t("Rendered Entity"),
      ],
      '#title' => $this->t('Video Formatter'),
      '#default_value' => $this->getSetting('video')['video_formatter'],
    ];

    $elements['video']['video_formatter_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getEntityDisplayModes(),
      '#title' => $this->t('Video View Mode'),
      '#default_value' => $this->getSetting('video')['video_formatter_view_mode'],
    ];

    $elements['audio']['audio_formatter'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'entity' => $this->t("Rendered Entity"),
      ],
      '#title' => $this->t('Audio Formatter'),
      '#default_value' => $this->getSetting('audio')['audio_formatter'],
    ];

    $elements['audio']['audio_formatter_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->getEntityDisplayModes(),
      '#title' => $this->t('Audio View Mode'),
      '#default_value' => $this->getSetting('audio')['audio_formatter_view_mode'],
    ];

    $elements['other']['view_mode'] = $vm;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Loop through each media type and try to find a render method.
    foreach ($items as $delta => $item) {
      $media_id = $item->getValue()['target_id'];
      $media_item = Media::load($media_id);
      $bundle = $media_item->bundle();
      $method_name = 'view' . ucfirst(strtolower($bundle)) . "Element";
      if (method_exists($this, $method_name)) {
        $vm_setting = $bundle . "_formatter_view_mode";
        $view_mode = (!empty($this->getSetting($vm_setting))) ? $this->getSetting($vm_setting) : 'default';
        $elements[] = $this->{$method_name}($items, $item, $media_item, $view_mode);
      }
      else {
        $elements[] = $this->viewDefaultElement($items, $item, $media_item, $this->getSetting('view_mode'));
      }
    }

    return $elements;
  }

  /**
   * Use view modes to generate a render array.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $items
   *   A list of EntityReferenceItems.
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   A single item from the FieldItemList object.
   * @param \Drupal\media\Entity\Media $media
   *   An instantiated Media Object.
   * @param string $view_mode
   *   The view mode machine name.
   *
   * @return array
   *   A render array.
   */
  private function viewImageElement($items, $item, $media, $view_mode = "default") {
    $op = $this->getSetting('image_formatter');

    if ($op == "image_style") {
      $formatter = new MediaImageFormatter($this->pluginId, $this->pluginDefinition, $this->fieldDefinition, $this->settings, $this->label, $view_mode, $this->thirdPartySettings, $this->loggerFactory, $this->entityTypeManager, $this->entityDisplayRepository);
    }

    if ($op == "responsive_image_style") {

    }

    return $this->entityTypeManager->getViewBuilder('media')->view($media, $view_mode);
  }

  /**
   * Use view modes to generate a render array.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $items
   *   A list of EntityReferenceItems.
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *   A single item from the FieldItemList object.
   * @param \Drupal\media\Entity\Media $media
   *   An instantiated Media Object.
   * @param string $view_mode
   *   The view mode machine name.
   *
   * @return array
   *   A render array for a view mode.
   */
  private function viewDefaultElement($items, $item, $media, $view_mode = "default") {
    return $this->entityTypeManager->getViewBuilder('media')->view($media, $view_mode);
  }

  /**
   * Get available responsive image styles.
   *
   * @todo: Refactor so this isn't duping MediaResponsiveImageFormatter.
   *
   * @return array
   *   Keyed array of image styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
