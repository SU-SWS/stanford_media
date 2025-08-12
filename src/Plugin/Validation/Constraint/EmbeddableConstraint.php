<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraint;

/**
 * Class EmbeddableConstraint.
 */
#[Constraint(
  id: 'embeddable',
  label: new TranslatableMarkup('Embeddable', [], ['context' => 'Validation']),
  type: ['string']
)]
class EmbeddableConstraint extends OEmbedResourceConstraint {

  public $embedCodeNotAllowed = 'The given embeddable code is not permitted.';

}
