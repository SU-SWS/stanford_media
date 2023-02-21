<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class MediaFormatter.
 *
 * @package Drupal\stanford_media\Plugin\Field\FieldFormatter
 */
abstract class MediaImageFormatterBase extends MediaFormatterBase implements TrustedCallbackInterface {

  /**
   * Get an array of image style options in order to choose and apply in render.
   *
   * @return string[]
   *   Keyed array of style options.
   */
  abstract protected function getStyleOptions(): array;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRender'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = [
      'image_style' => NULL,
      'link' => 0,
      'remove_alt' => FALSE,
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

    $elements['image_style'] = [
      '#type' => 'select',
      '#options' => $this->getStyleOptions(),
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->getSetting('image_style') ?: '',
      '#empty_option' => $this->t('Use Entity Display'),
    ];
    $elements['link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link Media to Parent'),
      '#default_value' => $this->getSetting('link'),
    ];
    $elements['remove_alt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Alt Attribute'),
      '#description' => $this->t('If the image is always "decorative", remove the alt text that might exist on the media image.'),
      '#default_value' => $this->getSetting('remove_alt') ?: FALSE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Can't test this with unit tests since the parent method has a global t()
   * function usage.
   *
   * @codeCoverageIgnore
   */
  public function settingsSummary() {
    $image_styles = $this->getStyleOptions();
    $summary = parent::settingsSummary();

    unset($image_styles['']);
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Use Entity Display');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $image_styles = $this->getStyleOptions();
    $style = $this->getSetting('image_style');

    if (empty($style) || !isset($image_styles[$style])) {
      return $elements;
    }

    $remove_alt = (bool) $this->getSetting('remove_alt') ?? FALSE;
    foreach ($elements as &$element) {
      $element['#stanford_media_image_style'] = $style;
      $element['#pre_render'][] = [get_class($this), 'preRender'];
      $element['#stanford_media_remove_alt'] = $remove_alt;

      if ($this->getSetting('link')) {
        /** @var \Drupal\Core\Entity\EntityInterface $parent */
        $parent = $items->getParent()->getValue();

        $element['#stanford_media_url'] = $parent->toUrl();
        $element['#stanford_media_url_title'] = $parent->label();
        $element['#cache']['keys'][] = substr(md5($element['#stanford_media_url']->toUriString()), 0, 5);
      }
      $element['#cache']['keys'][] = $style;
      if ($remove_alt) {
        $element['#cache']['keys'][] = 'no-alt';
      }
    }

    return $elements;
  }

  /**
   * Change the render array to use the desired image style.
   *
   * @param mixed $element
   *   Render array to change.
   *
   * @return array
   *   Altered render array.
   */
  abstract public static function preRender($element): array;

}
