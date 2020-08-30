<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraint;

/**
 * Class EmbeddableConstraint.
 *
 * @Constraint(
 *   id = "embeddable",
 *   label = @Translation("Embeddable", context = "Validation"),
 *   type = "string"
 * )
 */
class EmbeddableConstraint extends OEmbedResourceConstraint {

  // Extra constraints for the Embeddable media type should go here.

}
