<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\stanford_media\Plugin\EmbedValidatorPluginManager;
use Drupal\stanford_media\Plugin\media\Source\EmbeddableInterface;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraint;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class GoogleFormsConstraintValidatorTest
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
  public function setup(): void {
    parent::setUp();

    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    $this->validationContext = new ExecutionContext($validator, NULL, $translator);

    $url_resolver = $this->createMock(UrlResolverInterface::class);
    $resource_fetcher = $this->createMock(ResourceFetcherInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $embed_validation = $this->createMock(EmbedValidatorPluginManager::class);
    $account = $this->createMock(AccountProxyInterface::class);

    $container = new ContainerBuilder();
    $container->set('media.oembed.url_resolver', $url_resolver);
    $container->set('media.oembed.resource_fetcher', $resource_fetcher);
    $container->set('logger.factory', $logger_factory);
    $container->set('plugin.manager.embed_validator_plugin_manager', $embed_validation);
    $container->set('current_user', $account);
    \Drupal::setContainer($container);

    $this->validator = EmbeddableConstraintValidator::create(\Drupal::getContainer());
    $this->validator->initialize($this->validationContext);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Non google form source will throw an error.
   */
  public function testValidationNonEmbeddable() {
    $source = $this->createMock(MediaSourceInterface::class);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(EmbeddableConstraint::class);

    $this->expectException(\LogicException::class);
    $this->validator->validate($field_item_list, $constraint);
  }

  public function testOembedEmbeddable(){
    $source = $this->createMock(EmbeddableInterface::class);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(EmbeddableConstraint::class);

    $this->validator->validate($field_item_list, $constraint);
    $this->assertStringContainsString('valid oEmbed resource', $this->validationContext->getViolations()->get(0)->getMessageTemplate());
  }

  public function testUnstructuredEmbeddable(){
    $source = $this->createMock(EmbeddableInterface::class);
    $source->method('hasUnstructured')->willReturn(TRUE);
    $source->method('embedCodeIsAllowed')->willReturn(TRUE);
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($source);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getEntity')->willReturn($entity);
    $constraint = $this->createMock(EmbeddableConstraint::class);

    $this->validationContext->setNode(NULL, $entity, NULL, 'foo');

    $this->validator->validate($field_item_list, $constraint);
    $this->assertEquals(0, $this->validationContext->getViolations()->count());
  }

}
