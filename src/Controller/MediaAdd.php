<?php

namespace Drupal\stanford_media\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaAdd to provide links to upload media.
 *
 * @package Drupal\stanford_media\Controller
 */
class MediaAdd extends EntityController {

  /**
   * Finds which media type is appropriate.
   *
   * @var \Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface
   */
  protected $bundleSuggestion;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.bundle_suggestion_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, RouteMatchInterface $route_match, BundleSuggestionManagerInterface $bundle_suggestion) {
    parent::__construct($entity_type_manager, $bundle_info, $entity_repository, $renderer, $string_translation, $url_generator, $route_match);
    $this->bundleSuggestion = $bundle_suggestion;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($entity_type_id) {
    $page = parent::addPage($entity_type_id);
    $bulk_bundles = [];

    foreach ($this->bundleSuggestion->getUploadBundles() as $media_type) {
      unset($page['#bundles'][$media_type->id()]);
      $bulk_bundles[] = $media_type->label();
    }

    // No media bundles that allow upload.
    if (empty($bulk_bundles)) {
      return $page;
    }

    // Add a bulk upload for all bundles with upload ability.
    $url = new Url('stanford_media.bulk_upload');
    $page['#bundles']['bulk'] = [
      'label' => $this->t('Upload File(s)'),
      'description' => $this->t('Add one or more files to the Media Library.'),
      'add_link' => new Link($this->t('Bulk Upload'), $url),
    ];

    return $page;
  }

}
