<?php

namespace Drupal\Tests\stanford_media\Unit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Controller\MediaAdd;
use Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class MediaAddTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Controller\MediaAdd
 */
class MediaAddTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * If the bundle suggestion callback should return bundles.
   *
   * @var bool
   */
  protected $returnUploadBundles = FALSE;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    $media_definition = $this->createMock(EntityTypeInterface::class);
    $media_definition->method('getKey')->willReturn('bundle');
    $media_definition->method('getBundleEntityType')->willReturn('media_type');

    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $access_handler->method('createAccess')
      ->willReturn(AccessResult::allowed());

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->willReturn($media_definition);
    $entity_type_manager->method('getAccessControlHandler')
      ->willReturn($access_handler);

    $bundle_info = $this->createMock(EntityTypeBundleInfoInterface::class);
    $bundle_info->method('getBundleInfo')->willReturn([
      'file' => ['label' => 'File'],
      'video' => ['label' => 'Video'],
      'image' => ['label' => 'Image'],
    ]);

    $entity_repo = $this->createMock(EntityRepositoryInterface::class);
    $renderer = $this->createMock(RendererInterface::class);

    $bundle_suggestion = $this->createMock(BundleSuggestionManagerInterface::class);
    $bundle_suggestion->method('getUploadBundles')
      ->willReturnCallback([$this, 'getUploadBundlesCallback']);

    $url_generator = $this->createMock(UrlGeneratorInterface::class);

    $link_generator = $this->createMock(LinkGeneratorInterface::class);

    $current_route_match = $this->createMock(CurrentRouteMatch::class);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('entity_type.bundle.info', $bundle_info);
    $this->container->set('entity.repository', $entity_repo);
    $this->container->set('renderer', $renderer);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('url_generator', $url_generator);
    $this->container->set('plugin.manager.bundle_suggestion_manager', $bundle_suggestion);
    $this->container->set('link_generator', $link_generator);
    $this->container->set('current_route_match', $current_route_match);
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the controller consolidates the links.
   */
  public function testController() {
    $controller = MediaAdd::create($this->container);
    $page = $controller->addPage('media');
    $this->assertCount(3, $page['#bundles']);

    $this->returnUploadBundles = TRUE;

    $page = $controller->addPage('media');
    $this->assertCount(2, $page['#bundles']);
  }

  /**
   * Mock bundle suggestion callback.
   *
   * @return array
   *   Array of file upload media type bundles.
   */
  public function getUploadBundlesCallback() {
    if (!$this->returnUploadBundles) {
      return [];
    }

    $file_media_type = $this->createMock(MediaTypeInterface::class);
    $file_media_type->method('id')->willReturn('file');
    $file_media_type->method('label')->willReturn('File');

    $image_media_type = $this->createMock(MediaTypeInterface::class);
    $image_media_type->method('id')->willReturn('image');
    $image_media_type->method('label')->willReturn('Image');

    return ['file' => $file_media_type, 'image' => $image_media_type];
  }

}
