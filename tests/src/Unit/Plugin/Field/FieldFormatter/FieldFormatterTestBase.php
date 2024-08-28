<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Drupal\Tests\UnitTestCase;

abstract class FieldFormatterTestBase extends UnitTestCase {

  /**
   * Drupal dependency container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Mock field storage object.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * @var bool
   */
  protected $fieldTargetIsMedia = TRUE;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);

    $entity_definition = $this->createMock(EntityTypeInterface::class);
    $entity_definition->method('hasViewBuilderClass')->willReturn(TRUE);

    $responsive_style = $this->createMock(ResponsiveImageStyleInterface::class);
    $responsive_style->method('label')->willReturn('Foo');

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('loadMultiple')
      ->willReturn(['foo' => $responsive_style]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);
    $entity_type_manager->method('getDefinition')
      ->willReturn($entity_definition);

    $entity_display_repo = $this->createMock(EntityDisplayRepositoryInterface::class);

    $this->container = new ContainerBuilder();
    $this->container->set('logger.factory', $logger_factory);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('entity_display.repository', $entity_display_repo);
    $this->container->set('entity.manager', $entity_type_manager);

    \Drupal::setContainer($this->container);

    $field_storage = $this->createMock(FieldStorageConfigInterface::class);
    $field_storage->method('getSetting')
      ->willReturnCallback([$this, 'fieldStorageGetSettingCallback']);

    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $this->fieldDefinition->method('getFieldStorageDefinition')
      ->willReturn($field_storage);
  }

  protected function getMockMediaEntity() {
    $media_source = $this->createMock(MediaSourceInterface::class);
    $media_source->method('getConfiguration')
      ->willReturn(['source_field' => 'field_foo']);

    $entity = $this->createMock(MediaInterface::class);
    $entity->method('getSource')->willReturn($media_source);
    return $entity;
  }

  public function fieldStorageGetSettingCallback() {
    return $this->fieldTargetIsMedia ? 'media' : 'node';
  }

}
