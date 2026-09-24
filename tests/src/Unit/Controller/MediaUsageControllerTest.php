<?php

namespace Drupal\Tests\stanford_media\Unit\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_media\Controller\MediaUsageController;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the media usage controller.
 */
#[Group('stanford_media')]
class MediaUsageControllerTest extends UnitTestCase {

  /**
   * Controller being tested.
   *
   * @var \Drupal\stanford_media\Controller\MediaUsageController
   */
  protected $controller;

  /**
   * Cached data keyed by cache id.
   *
   * @var array
   */
  protected $cacheData = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // A node that is used directly by the media.
    $node = $this->getEntity(1, 'Node Title', TRUE, TRUE);
    // A second node that only uses the media through a paragraph.
    $paragraph_parent = $this->getEntity(2, 'Paragraph Parent', FALSE, TRUE);
    // A paragraph that has a parent, and one that has no canonical parent.
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getParentEntity')->willReturn($paragraph_parent);
    $orphan_paragraph = $this->createMock(ParagraphInterface::class);
    $orphan_paragraph->method('getParentEntity')->willReturn(NULL);
    $orphan_paragraph->method('hasLinkTemplate')->willReturn(FALSE);

    $node_storage = $this->createMock(EntityStorageInterface::class);
    // Include the node twice to ensure it's deduped.
    $node_storage->method('loadMultiple')->willReturn([$node, $node]);
    $paragraph_storage = $this->createMock(EntityStorageInterface::class);
    $paragraph_storage->method('loadMultiple')
      ->willReturn([$paragraph, $orphan_paragraph]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['node', $node_storage],
        ['paragraph', $paragraph_storage],
      ]);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnCallback(fn($cid) => $this->cacheData[$cid] ?? FALSE);
    $cache->method('set')->willReturnCallback(function ($cid, $data) {
      $this->cacheData[$cid] = (object) ['data' => $data];
    });

    $entity_usage = $this->createMock(EntityUsageInterface::class);
    $entity_usage->method('listSources')->willReturn([
      'node' => [1 => [], 3 => []],
      'paragraph' => [4 => [], 5 => []],
    ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('cache.default', $cache);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('entity_usage.usage', $entity_usage);
    \Drupal::setContainer($container);

    $this->controller = MediaUsageController::create($container);
  }

  /**
   * Test the page title.
   */
  public function testTitle(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('label')->willReturn('Foo Bar');
    $this->assertEquals('Media Usage: Foo Bar', (string) $this->controller->title($media));
  }

  /**
   * Test the usage table is built from the parent entities and then cached.
   */
  public function testView(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('id')->willReturn(99);

    $build = $this->controller->view($media);
    $this->assertEquals('table', $build['#theme']);
    // The duplicate node and the orphaned paragraph are skipped.
    $this->assertCount(2, $build['#rows']);

    $this->assertEquals('Node Title', $build['#rows'][0]['title']['data']['#title']);
    $links = $build['#rows'][0]['operations']['data']['#links'];
    $this->assertArrayHasKey('view', $links);
    $this->assertArrayHasKey('edit', $links);

    // The paragraph parent is only editable, not viewable.
    $this->assertEquals('Paragraph Parent', $build['#rows'][1]['title']['data']['#title']);
    $links = $build['#rows'][1]['operations']['data']['#links'];
    $this->assertArrayNotHasKey('view', $links);
    $this->assertArrayHasKey('edit', $links);

    $this->assertArrayHasKey('stanford_media:media_usage:99', $this->cacheData);

    // Change the cached data to verify the cache is used the second time.
    $this->cacheData['stanford_media:media_usage:99']->data = ['foo'];
    $this->assertEquals(['foo'], $this->controller->view($media)['#rows']);
  }

  /**
   * Get a mocked entity with a canonical link template.
   *
   * @param int $id
   *   Entity id.
   * @param string $label
   *   Entity label.
   * @param bool $view_access
   *   If the user can view the entity.
   * @param bool $update_access
   *   If the user can update the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Mocked entity.
   */
  protected function getEntity(int $id, string $label, bool $view_access, bool $update_access): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn($id);
    $entity->method('label')->willReturn($label);
    $entity->method('hasLinkTemplate')->willReturn(TRUE);
    $entity->method('toUrl')->willReturn($this->createMock(Url::class));
    $entity->method('access')
      ->willReturnCallback(fn($operation) => $operation == 'view' ? $view_access : $update_access);
    return $entity;
  }

}
