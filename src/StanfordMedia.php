<?php

namespace Drupal\stanford_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class StanfordMedia.
 */
class StanfordMedia implements TrustedCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['imageWidgetProcess', 'imageWidgetValue'];
  }

  /**
   * Process callback for image widget fields.
   *
   * @param array $element
   *   Field element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $form
   *   Complete form.
   *
   * @return array
   *   Modified field element.
   */
  public static function imageWidgetProcess(array $element, FormStateInterface $form_state, array $form) {
    if (!empty($element['alt']['#description'])) {
      $url = Url::fromUri('https://uit.stanford.edu/accessibility/concepts/images');
      $link = Link::fromTextAndUrl($url->toString(), $url)->toString();
      $element['alt']['#description'] = new TranslatableMarkup('Short description of the image used by screen readers and displayed when the image is not loaded. Leave blank if image is decorative. Learn more about alternative text: @link', ['@link' => $link]);
    }
    if ($element['#alt_field_required'] || !$element['alt']['#access']) {
      return $element;
    }

    $element['decorative'] = [
      '#type' => 'checkbox',
      '#title' => t('Decorative Image'),
      '#description' => t('Check this only if the image presents no additional information to the viewer.'),
      '#weight' => -99,
      '#default_value' => empty($element['alt']['#default_value']),
      '#attributes' => ['data-decorative' => TRUE],
    ];
    $element['alt']['#states']['visible'][':input[data-decorative]']['checked'] = FALSE;
    $element['alt']['#value_callback'] = [self::class, 'imageAltValue'];

    return $element;
  }

  /**
   * Image alt text field value callback.
   *
   * @param array $element
   *   Form element.
   * @param string|bool $input
   *   User entered value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return string
   *   Adjusted input string.
   */
  public static function imageAltValue(array &$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'];
    }
    $parents = array_slice($element['#parents'], 0, -1);
    $decorative_path = $parents;
    $decorative_path[] = 'decorative';
    if ($form_state->getValue($decorative_path)) {
      $form_state->setValue($element['#parents'], '');
      return '';
    }
    return $input;
  }

}
