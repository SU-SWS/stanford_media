<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EntityBrowser\SelectionDisplay;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_browser\FieldWidgetDisplayInterface;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\EntityBrowser\SelectionDisplay\MultiStepSelection;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MultiStepSelectionTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EntityBrowser\SelectionDisplay\MultiStepSelection
 */
class MultiStepSelectionTest extends UnitTestCase {

  /**
   * Selection display plugin.
   *
   * @var \Drupal\stanford_media\Plugin\EntityBrowser\SelectionDisplay\MultiStepSelection
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('loadMultiple')
      ->will($this->returnCallback([$this, 'loadMultipleCallback']));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $widget_display = $this->createMock(FieldWidgetDisplayInterface::class);
    $widget_display->method('view')->willReturn($this->randomMachineName());

    $widget_display_manager = $this->createMock(FieldWidgetDisplayManager::class);
    $widget_display_manager->method('createInstance')
      ->willReturn($widget_display);

    $this->container = new ContainerBuilder();
    $this->container->set('event_dispatcher', $event_dispatcher);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('plugin.manager.entity_browser.field_widget_display', $widget_display_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);

    $this->plugin = MultiStepSelection::create($this->container, [], '', []);
  }

  public function testForm() {
    $this->assertInstanceOf(MultiStepSelection::class, $this->plugin);
    $original_form = [];
    $original_form['widget']['view'] = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#name' => 'foo',
      '#array_parents' => ['widget_selector'],
    ]);
    $form = $this->plugin->getForm($original_form, $form_state);

    $this->assertNull($form_state->get('dropzonjs'));
    $this->assertNull($form_state->get('embed_code'));
    $this->assertArrayEquals(['view'], $original_form['#attributes']['class']);

    $this->assertTrue(in_array('stanford_media/multi_step', $form['#attached']['library']));
    $this->assertArrayEquals([
      '#prefix' => '<div id="message">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ], $form['selected']['message']);
  }

  public function testFormSelectedEntities() {
    $original_form = [];
    $original_form['widget']['view'] = [];
    $form_state = new FormState();
    $entities = [
      $this->createMediaEntity(),
      $this->createMediaEntity(),
    ];

    $form_state->set(['entity_browser', 'selected_entities'], $entities);
    $form = $this->plugin->getForm($original_form, $form_state);

    $this->assertCount(6, $form['selected']);
    $test_entity_id = reset($entities)->id();
    $this->assertArrayHasKey("items_{$test_entity_id}_0", $form['selected']);
  }

  public function testAjaxCommand() {
    $form = [];
    $form_state = new FormState();
    $form_state->set('process_ajax', TRUE);
    $ajax = $this->plugin::handleAjaxCommand($form, $form_state);
    $this->assertInstanceOf(AjaxResponse::class, $ajax);

    $this->assertArrayEquals([
      [
        'command' => 'insert',
        'method' => 'replaceWith',
        'selector' => 'div[id="message"]',
        'data' => '<div id="message"></div>',
        'settings' => NULL,
      ],
    ], $ajax->getCommands());

    $form_state->set('process_ajax', FALSE);
    $form_state->set([
      'entity_browser',
      'validators',
      'cardinality',
      'cardinality',
    ], 1);
    $ajax = $this->plugin::handleAjaxCommand($form, $form_state);
    $this->assertArrayEquals([
      [
        'command' => 'insert',
        'method' => 'replaceWith',
        'selector' => 'div[id="message"]',
        'data' => '<div id="message"><div class="messages messages--error">Only <em class="placeholder">1</em> item can be used.</div></div>',
        'settings' => NULL,
      ],
    ], $ajax->getCommands());
  }

  public function testRemoveJsCommand() {
    $original_form = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#name' => 'ajax_commands_handler',
      '#value' => json_encode([
        'remove' => [
          ['entity_id' => 'items_1_0'],
        ],
      ]),
    ]);
    $form_state->set([
      'entity_browser',
      'selected_entities',
    ], [$this->createMediaEntity(), $this->createMediaEntity()]);
    $form = $this->plugin->getForm($original_form, $form_state);
    $this->assertTrue(TRUE);
  }

  public function testAddJsCommand() {
    $original_form = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#name' => 'ajax_commands_handler',
      '#value' => json_encode([
        'add' => [
          ['entity_id' => 'media:' . rand(0, 100)],
          ['entity_id' => 'media:' . rand(0, 100)],
        ],
      ]),
    ]);
    $form_state->set(['entity_browser', 'selected_entities'], []);
    $form = $this->plugin->getForm($original_form, $form_state);
    $this->assertTrue(TRUE);
  }

  protected function createMediaEntity($id = NULL) {
    $entity = $this->createMock(MediaInterface::class);
    $entity->method('id')->willReturn($id ?: rand(0, 100));
    $entity->method('label')->willReturn($this->getRandomGenerator()->string());
    return $entity;
  }

  public function loadMultipleCallback($entity_ids) {
    $entities = [];
    foreach ($entity_ids as $entity_id) {
      $entities[$entity_id] = $this->createMediaEntity($entity_id);
    }
    return $entities;
  }

}
