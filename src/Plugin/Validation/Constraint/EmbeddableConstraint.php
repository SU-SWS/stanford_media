<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class EmbeddableConstraint.
 *
 * @Constraint(
 *   id = "embeddable",
 *   label = @Translation("Embeddable", context = "Validation"),
 *   type = "string"
 * )
 */
class EmbeddableConstraint extends Constraint {

  public $invalidString = 'The given data does not contain a valid url';

  public $invalidUrl = 'The given URL is not a google forms url.';

}
