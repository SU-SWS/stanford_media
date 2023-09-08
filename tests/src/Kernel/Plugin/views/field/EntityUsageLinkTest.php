<?php

namespace Drupal\Tests\stanford_media\Kernel\Plugin\views\field;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_media\Plugin\views\field\EntityUsageLink;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Class EntityUsageLinkTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\views\field\EntityUsageLink
 */
class EntityUsageLinkTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'node',
    'system',
    'user',
  ];

  /**
   * Created node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    NodeType::create(['type' => 'article'])
      ->save();

    $this->node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
    ]);
    $this->node->save();
  }

  /**
   * Test the field returns a rendered text.
   */
  public function testViewField() {
    $plugin = EntityUsageLink::create(\Drupal::getContainer(), [], '', []);
    $view = $this->createMock(ViewExecutable::class);

    $query = $this->createMock(Sql::class);
    $query->method('ensureTable')->willReturn('foo');
    $query->method('addField')->will($this->returnCallback([
      $this,
      'addFieldCallback',
    ]));
    $view->query = $query;

    $display = $this->createMock(DisplayPluginBase::class);
    $plugin->init($view, $display);

    $plugin->query();
    $this->assertEquals('foo_source_type', $plugin->field_alias);
    $this->assertEquals('foo_source_id', $plugin->field_alias_id);

    $values = $this->createMock(ResultRow::class);
    $values->foo_source_type = 'node';
    $values->foo_source_id = NULL;
    $this->assertEmpty($plugin->render($values));

    $values->foo_source_id = $this->node->id();
    $result = $plugin->render($values);
    $result = \Drupal::service('renderer')->renderPlain($result);

    $expression = sprintf('/<a href="\/node\/%s.*<\/a>/', $this->node->id());
    preg_match($expression, $result, $preg_match);
    $this->assertNotEmpty($preg_match);
  }

  /**
   * Mock views query callback.
   */
  public function addFieldCallback($table_alias, $type) {
    return $table_alias . '_' . $type;
  }

}
