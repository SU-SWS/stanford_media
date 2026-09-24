<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Class GoogleFormsConstraint.
 */
#[ConstraintAttribute(
  id: 'google_forms',
  label: new TranslatableMarkup('Google Forms', [], ['context' => 'Validation']),
  type: ['string']
)]
class GoogleFormsConstraint extends Constraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $invalidString = 'The given data does not contain a valid url',
    public $invalidUrl = 'The given URL is not a google forms url.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
