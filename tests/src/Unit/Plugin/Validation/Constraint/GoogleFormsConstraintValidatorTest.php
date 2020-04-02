<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Validation\ExecutionContext;
use Drupal\Core\Validation\TranslatorInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\stanford_media\Plugin\media\Source\GoogleForm;
use Drupal\stanford_media\Plugin\Validation\Constraint\GoogleFormsConstraint;
use Drupal\stanford_media\Plugin\Validation\Constraint\GoogleFormsConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class GoogleFormsConstraintValidatorTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Validation\Constraint\GoogleFormsConstraintValidator
 */
class GoogleFormsConstraintValidatorTest extends UnitTestCase {

  /**
   * Validator plugin.
   *
   * @var \Drupal\stanford_media\Plugin\Validation\Constraint\GoogleFormsConstraintValidator
   */
  protected $validator;

  protected $validationContext;

  protected $sourceFieldValue;

  protected function setUp() {
    parent::setUp();

    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    $this->validationContext = new ExecutionContext($validator, NULL, $translator);

    $this->validator = new GoogleFormsConstraintValidator();
    $this->validator->initialize($this->validationContext);
  }

  public function testValidationNonGoogleForm() {
    $source = $this->createMock(MediaSourceInterface::class);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(GoogleFormsConstraint::class);

    $this->expectException(\LogicException::class);
    $this->validator->validate($field_item_list, $constraint);
  }

  public function testValidationGoogleForm() {
    $source = $this->createMock(GoogleForm::class);
    $source->method('getSourceFieldValue')->willReturnReference($this->sourceFieldValue);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(GoogleFormsConstraint::class);

    $this->sourceFieldValue = 'not a url';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(1, $this->validationContext->getViolations()->count());
    $this->validationContext->getViolations()->remove(0);

    $this->sourceFieldValue = 'http://notgoogleform.com';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(1, $this->validationContext->getViolations()->count());
    $this->validationContext->getViolations()->remove(1);

    $this->sourceFieldValue = 'http://google.com/forms/this/is/valid/viewform';
    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(0, $this->validationContext->getViolations()->count());
  }

}
