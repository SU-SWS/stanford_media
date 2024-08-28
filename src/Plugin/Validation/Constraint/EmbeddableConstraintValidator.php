<?php

namespace Drupal\stanford_media\Plugin\Validation\Constraint;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\stanford_media\Plugin\EmbedValidatorPluginManager;
use Drupal\stanford_media\Plugin\media\Source\EmbeddableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraintValidator;

/**
 * Validation constraint for Embeddables.
 */
class EmbeddableConstraintValidator extends OEmbedResourceConstraintValidator {

  /**
   * Embed validation plugin manager.
   *
   * @var \Drupal\stanford_media\Plugin\EmbedValidatorPluginManager
   */
  protected $validationManager;

  /**
   * Current account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.embed_validator_plugin_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(UrlResolverInterface $url_resolver, ResourceFetcherInterface $resource_fetcher, LoggerChannelFactoryInterface $logger_factory, EmbedValidatorPluginManager $validation_plugin_manager, AccountProxyInterface $current_user) {
    parent::__construct($url_resolver, $resource_fetcher, $logger_factory);
    $this->validationManager = $validation_plugin_manager;
    $this->account = $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public function validate($value, Constraint $constraint): void {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $value->getEntity();
    $source = $media->getSource();

    if (!($source instanceof EmbeddableInterface)) {
      throw new \LogicException('Media source must implement ' . EmbeddableInterface::class);
    }

    // If this is an unstructured embed, do our validation here.
    // Otherwise, pass it along to the oEmbed validation.
    if (!$source->hasUnstructured($media)) {
      parent::validate($value, $constraint);
      return;
    }

    $unstructured_field = $source->getConfiguration()['unstructured_field_name'] ?? '';
    if (
      $this->account->hasPermission('bypass embed field validation') ||
      $this->context->getPropertyPath() != $unstructured_field
    ) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    if (!$source->embedCodeIsAllowed($value->getString())) {
      $this->context->addViolation($constraint->embedCodeNotAllowed);
    }
  }

}
