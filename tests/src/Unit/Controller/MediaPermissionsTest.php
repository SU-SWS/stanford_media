<?php

namespace Drupal\Tests\stanford_media\Unit\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Controller\MediaPermissions;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class MediaPermissionsTest.
 */
#[Group('stanford_media')]
class MediaPermissionsTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $this->entityTypeManager);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);
  }

  /**
   * Test standAlonePermissions returns empty array when standalone URL
   * disabled.
   */
  public function testStandAlonePermissionsDisabled() {
    $media_settings = $this->createMock(ImmutableConfig::class);
    $media_settings->method('get')
      ->with('standalone_url')
      ->willReturn(FALSE);

    $this->configFactory->method('get')
      ->with('media.settings')
      ->willReturn($media_settings);

    $controller = MediaPermissions::create($this->container);
    $permissions = $controller->standAlonePermissions();

    $this->assertIsArray($permissions);
    $this->assertEmpty($permissions);
  }

  /**
   * Test standAlonePermissions generates permissions for media types.
   */
  public function testStandAlonePermissionsEnabled() {
    $media_settings = $this->createMock(ImmutableConfig::class);
    $media_settings->method('get')
      ->with('standalone_url')
      ->willReturn(TRUE);

    $this->configFactory->method('get')
      ->with('media.settings')
      ->willReturn($media_settings);

    $video_type = $this->createMock(MediaTypeInterface::class);
    $video_type->method('id')->willReturn('video');
    $video_type->method('label')->willReturn('Video');

    $image_type = $this->createMock(MediaTypeInterface::class);
    $image_type->method('id')->willReturn('image');
    $image_type->method('label')->willReturn('Image');

    $file_type = $this->createMock(MediaTypeInterface::class);
    $file_type->method('id')->willReturn('file');
    $file_type->method('label')->willReturn('File');

    $media_types = [
      'video' => $video_type,
      'image' => $image_type,
      'file' => $file_type,
    ];

    $media_storage = $this->createMock(EntityStorageInterface::class);
    $media_storage->method('loadMultiple')
      ->willReturn($media_types);

    $this->entityTypeManager->method('getStorage')
      ->with('media_type')
      ->willReturn($media_storage);

    $controller = MediaPermissions::create($this->container);
    $permissions = $controller->standAlonePermissions();

    $this->assertIsArray($permissions);
    $this->assertCount(3, $permissions);
    $this->assertArrayHasKey('view video media standalone url', $permissions);
    $this->assertArrayHasKey('view image media standalone url', $permissions);
    $this->assertArrayHasKey('view file media standalone url', $permissions);

    $this->assertEquals('View %name standalone url', $permissions['view video media standalone url']['title']->getUntranslatedString());
    $this->assertEquals(['%name' => 'Video'], $permissions['view video media standalone url']['title']->getArguments());

    $this->assertEquals('View %name standalone url', $permissions['view image media standalone url']['title']->getUntranslatedString());
    $this->assertEquals(['%name' => 'Image'], $permissions['view image media standalone url']['title']->getArguments());

    $this->assertEquals('View %name standalone url', $permissions['view file media standalone url']['title']->getUntranslatedString());
    $this->assertEquals(['%name' => 'File'], $permissions['view file media standalone url']['title']->getArguments());
  }

  /**
   * Test standAlonePermissions handles no media types.
   */
  public function testStandAlonePermissionsNoMediaTypes() {
    $media_settings = $this->createMock(ImmutableConfig::class);
    $media_settings->method('get')
      ->with('standalone_url')
      ->willReturn(TRUE);

    $this->configFactory->method('get')
      ->with('media.settings')
      ->willReturn($media_settings);

    $media_storage = $this->createMock(EntityStorageInterface::class);
    $media_storage->method('loadMultiple')
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('media_type')
      ->willReturn($media_storage);

    $controller = MediaPermissions::create($this->container);
    $permissions = $controller->standAlonePermissions();

    $this->assertIsArray($permissions);
    $this->assertEmpty($permissions);
  }

  /**
   * Test controller create method.
   */
  public function testCreate() {
    $controller = MediaPermissions::create($this->container);
    $this->assertInstanceOf(MediaPermissions::class, $controller);
  }

}
