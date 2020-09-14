<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\stanford_media\Plugin\media\Source\Embeddable;
use Symfony\Component\Validator\Constraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraintValidator;

/**
 * Validation constraint for Embeddables.
 */
class EmbeddableConstraintValidator extends OEmbedResourceConstraintValidator {

  /**
   * {@inheritDoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $value->getEntity();
    $source = $media->getSource();

    if (!($source instanceof Embeddable)) {
      throw new \LogicException('Media source must implement ' . Embeddable::class);
    }

    // If this is an unstructured embed, do our validation here.
    // Otherwise, pass it along to the oEmbed validation.
    if ($source->hasUnstructured($media)) {
      // Do not allow oEmbed values on unstructured embeds.
      if ($source->hasOEmbed($media)) {
        $this->context->addViolation($constraint->oEmbedNotAllowed);
        return;
      }
      return;
    }

    parent::validate($value, $constraint);

  }

}
