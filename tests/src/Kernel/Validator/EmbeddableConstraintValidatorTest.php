<?php

namespace Drupal\Tests\stanford_media\Kernel\Validator;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraint;
use Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\media\Entity\MediaType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Drupal\Tests\media\Kernel\OEmbedResourceConstraintValidatorTest;

/**
 * @coversDefaultClass \Drupal\stanford_media\Plugin\Validation\Constraint\EmbeddableConstraintValidator
 *
 * @group media
 */
class EmbeddableConstraintValidatorTest extends OEmbedResourceConstraintValidatorTest {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system','path_alias','entity_test','field', 'file', 'image', 'media', 'user', 'stanford_media'];


  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $oembed_media;

  /**
   * Generated Media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $unstructured_media;

  /**
   * A test embed string.
   *
   * @var string
   */
  protected $iframe_code = '<iframe src="http://www.test.com"></iframe>';
  /**
   * Embeddable media type bundle.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->mediaType = MediaType::create([
      'id' => 'embeddable',
      'label' => 'embeddable',
      'source' => 'embeddable',
    ]);
    $this->mediaType->save();

    $this->mediaType
      ->set('source_configuration', [
        'oembed_field_name' => 'field_media_embeddable_oembed',
        'unstructured_field_name' => 'field_media_embeddable_code',
        'thumbnails_directory' => 'public://oembed_thumbnails',
        'source_field' => 'field_media_embeddable_oembed',
      ])
      ->save();


    // Create the fields we need.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_oembed',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'oembed',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_embeddable_code',
      'entity_type' => 'media',
      'type' => 'string_long',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'embeddable',
      'label' => 'unstructured',
    ])->save();


  }

  /**
   * @covers ::validate
   *
   */
  public function testValidate() {
    $media = Media::create([
      'bundle' => 'embeddable',
    ]);

    $constraint = new EmbeddableConstraint();

    // The media item has an empty source value, so the constraint validator
    // should add a violation and return early before invoking the URL resolver.
    $context = $this->prophesize(ExecutionContextInterface::class);
    $context->addViolation($constraint->invalidResourceMessage)->shouldBeCalled();

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $url_resolver->getProviderByUrl(Argument::any())->shouldNotBeCalled();

    $value = new class ($media) {
      public function __construct($entity) {
        $this->entity = $entity;
      }
      public function getEntity() {
        return $this->entity;
      }
    };

    $validator = new EmbeddableConstraintValidator(
      $url_resolver->reveal(),
      $this->container->get('media.oembed.resource_fetcher'),
      $this->container->get('logger.factory')
    );
    $validator->initialize($context->reveal());
    $validator->validate($value, $constraint);

    // The media has both an oEmbed URL and an embed code, so this should return a violation
    $media = Media::create([
      'bundle' => 'embeddable',
      'name' => 'unstructured embeddable',
      'field_media_embeddable_code' => '<iframe></iframe>',
      'field_media_embeddable_oembed' => 'http://www.test.com',
    ]);

    $value = new class ($media) {
      public function __construct($entity) {
        $this->entity = $entity;
      }
      public function getEntity() {
        return $this->entity;
      }
    };
    $context = $this->prophesize(ExecutionContextInterface::class);
    $context->addViolation($constraint->oEmbedNotAllowed)->shouldBeCalled();
    $validator->initialize($context->reveal());
    $validator->validate($value, $constraint);

  }

}
