<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraint;

/**
 * Checks that a value is a valid oEmbed URL or allowed embed code.
 */
#[Constraint(
  id: 'embeddable',
  label: new TranslatableMarkup('Embeddable', [], ['context' => 'Validation']),
  type: ['string']
)]
class EmbeddableConstraint extends OEmbedResourceConstraint {

  /**
   * Message shown when the embed code isn't on the allowed list.
   *
   * @var string
   */
  public $embedCodeNotAllowed = 'The given embeddable code is not permitted.';

}
