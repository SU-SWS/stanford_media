<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Validation\ExecutionContext;
use Drupal\Core\Validation\TranslatorInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraint;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class EmbeddableConstraintValidatorTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator
 */
class EmbeddableConstraintValidatorTest extends UnitTestCase {

  /**
   * Validator plugin.
   *
   * @var \Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator
   */
  protected $validator;

  /**
   * Validation context object.
   *
   * @var \Drupal\Core\TypedData\Validation\ExecutionContext
   */
  protected $validationContext;

  /**
   * Field value from mocked media entity.
   *
   * @var string
   */
  protected $sourceFieldValue;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    /*
    parent::setUp();

    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    $this->validationContext = new ExecutionContext($validator, NULL, $translator);

    $this->validator = new EmbeddableConstraintValidator();
    $this->validator->initialize($this->validationContext);
    */
  }

  /**
   * Non embeddable form source will throw an error.
   */
  public function testValidationNonEmbeddable() {
    /*
    $source = $this->createMock(MediaSourceInterface::class);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(EmbeddableConstraint::class);

    $this->expectException(\LogicException::class);
    $this->validator->validate($field_item_list, $constraint);
    */
  }

  /**
   * Various field values from the media will validate in different ways.
   */
  public function testValidationEmbeddable() {
    /*
    $source = $this->createMock(Embeddable::class);
    $source->method('getSourceFieldValue')->willReturnReference($this->sourceFieldValue);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(EmbeddableConstraint::class);

    $this->sourceFieldValue = 'not a url';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(1, $this->validationContext->getViolations()->count());
    $this->validationContext->getViolations()->remove(0);

    $this->sourceFieldValue = 'http://notEmbeddable.com';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(1, $this->validationContext->getViolations()->count());
    $this->validationContext->getViolations()->remove(1);

    $this->sourceFieldValue = 'http://google.com/forms/this/is/valid/viewform';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(0, $this->validationContext->getViolations()->count());
    */
  }

}
