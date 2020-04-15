<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class GoogleFormsConstraint.
 *
 * @Constraint(
 *   id = "google_forms",
 *   label = @Translation("Google Forms", context = "Validation"),
 *   type = "string"
 * )
 */
class GoogleFormsConstraint extends Constraint {

  public $invalidString = 'The given data does not contain a valid url';

  public $invalidUrl = 'The given URL is not a google forms url.';

}
