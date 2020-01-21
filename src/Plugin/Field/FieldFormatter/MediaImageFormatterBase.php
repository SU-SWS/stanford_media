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
   * @return array
   *   Keyed array of style options.
   */
  abstract protected function getStyleOptions();

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'image_style' => NULL,
      'link' => 0,
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

    foreach ($elements as &$element) {
      $element['#stanford_media_image_style'] = $style;
      $element['#pre_render'][] = [$this, 'preRender'];

      if ($this->getSetting('link')) {
        /** @var \Drupal\Core\Entity\EntityInterface $parent */
        $parent = $items->getParent()->getValue();

        $element['#stanford_media_url'] = $parent->toUrl();
        $element['#stanford_media_url_title'] = $parent->label();
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
  abstract public function preRender($element);

}
