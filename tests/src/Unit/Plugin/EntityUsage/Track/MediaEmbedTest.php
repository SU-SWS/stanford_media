<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EntityUsage\Track;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\stanford_media\Plugin\EntityUsage\Track\MediaEmbed;
use Drupal\Tests\UnitTestCase;

/**
 * Class MediaEmbedTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EntityUsage\Track\MediaEmbed
 */
class MediaEmbedTest extends UnitTestCase {

  /**
   * Test the entity usage track records the proper data.
   */
  public function testParse() {
    $configuration = [];
    $plugin_id = '';
    $plugin_definition = [];
    $usage_service = $this->createMock(EntityUsage::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $config_factory = $this->getConfigFactoryStub();
    $entity_repository = $this->createMock(EntityRepositoryInterface::class);
    $plugin = new MediaEmbed($configuration, $plugin_id, $plugin_definition, $usage_service, $entity_type_manager, $entity_field_manager, $config_factory, $entity_repository);

    $text = '<div><drupal-media data-entity-type="media" data-entity-uuid="foo-bar"></drupal-media></div>';
    $entities = $plugin->parseEntitiesFromText($text);
    $this->assertArrayEquals(['foo-bar' => 'media'], $entities);
  }

}
