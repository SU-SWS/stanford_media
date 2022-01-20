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
    return ['imageWidgetProcess'];
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
    return $element;
  }

}
