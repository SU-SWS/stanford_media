<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\stanford_media\Plugin\media\Source\GoogleForm;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 *
 * https://docs.google.com/forms/d/e/1FAIpQLSdzzOEFsPxmAqbPfgY85D4ov0T8iufnci-IELLefOYenU-iCA/viewform?embedded=true
 * https://docs.google.com/forms/d/e/1FAIpQLSdzzOEFsPxmAqbPfgY85D4ov0T8iufnci-IELLefOYenU-iCA/viewform?usp=sf_link
 */
class GoogleFormsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritDoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $value->getEntity();
    /** @var GoogleForm $source */
    $source = $media->getSource();

    if (!($source instanceof GoogleForm)) {
      throw new \LogicException('Media source must implement ' . GoogleForm::class);
    }
    $url = $source->getSourceFieldValue($media);

    preg_match('/http.*?"/', $url, $url_match);
    if (empty($url_match) || empty(parse_url($url_match[0]))) {
      $this->context->addViolation($constraint->invalidString);
    }
    $url = trim($url_match[0], '" ');

    preg_match('/forms\/([^ ]*)\/viewform/', $url, $form_id);
    if (!isset($form_id[1])) {
      $this->context->addViolation($constraint->invalidUrl);
    }
  }

}
