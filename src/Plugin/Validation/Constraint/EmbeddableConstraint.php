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

  public $oEmbedNotAllowed = 'An oEmbed link is not permitted on an Embeddable which has a custom Embed Code.';

}
