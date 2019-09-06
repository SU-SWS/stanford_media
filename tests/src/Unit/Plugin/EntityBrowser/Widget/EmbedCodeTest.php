<?php

namespace Drupal\Tests\stanford_media\Unit\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormState;
use Drupal\stanford_media\Plugin\EntityBrowser\Widget\EmbedCode;

/**
 * Class EmbedCodeTest
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Plugin\EntityBrowser\Widget\EmbedCode
 */
class EmbedCodeTest extends EntityBrowserWidgetTestBase {

  /**
   * If the mock duplication service should return any similar entities.
   *
   * @var bool
   */
  protected $returnSimilarItems = FALSE;

  /**
   * Flag to know if the method was called.
   *
   * @var bool
   */
  protected $iefSubmitCalled = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = TestEmbedCode::create($this->container, ['settings' => ['entity_browser_id' => $this->randomMachineName()]], '', ['auto_select' => FALSE]);
  }

  /**
   * Form structure with various possibilites.
   */
  public function testForm() {
    $this->assertInstanceOf(EmbedCode::class, $this->plugin);

    $original_form = [];
    $form_state = new FormState();
    $params = [];
    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $form_state->setCompleteForm($original_form);
    $this->assertCount(4, $form);

    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $this->assertEquals('textfield', $form['input']['#type']);
    $this->assertArrayNotHasKey(123, $form['entities']);

    $form_state->setValue('input', $this->randomMachineName());
    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $this->assertEquals('hidden', $form['input']['#type']);
    $this->assertArrayHasKey(123, $form['entities']);

    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $this->assertArrayHasKey(123, $form['entities']);
    $this->assertArrayNotHasKey('similar_items', $form['entities'][123]);

    // Duplication service now exists, but it doesn't return any similar items.
    $this->addDuplicationValidationService();
    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $this->assertArrayHasKey(123, $form['entities']);
    $this->assertArrayNotHasKey('similar_items', $form['entities'][123]);

    // Duplication service returns similar items.
    $this->returnSimilarItems = TRUE;
    $form = $this->plugin->getForm($original_form, $form_state, $params);
    $this->assertArrayHasKey('similar_items', $form['entities'][123]);
  }

  /**
   * Validation methods.
   */
  public function testValidation() {
    $form = [];
    $form['widget']['input']['#parents'] = [];
    $form_state = new FormState();
    $form_state->setValue('input', $this->randomMachineName());

    $this->plugin->validate($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $form_state->clearErrors();
    $this->returnBundleSuggestion = FALSE;
    $this->plugin->validate($form, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());
  }

  /**
   * Submit functionality.
   */
  public function testSubmit() {
    $form = [];
    $form_state = new FormState();
    $form_state->set(['entity_browser', 'selected_entities'], []);
    $form_state->setValue('input', $this->randomMachineName());
    $params = [];
    $element = $this->plugin->getForm($form, $form_state, $params);

    $this->plugin->submit($element, $form, $form_state);
    $this->assertFalse($this->iefSubmitCalled);

    $element['entities'][123]['entity_form']['#ief_element_submit'] = [
      [$this, 'iefElementSubmit'],
    ];
    $this->plugin->submit($element, $form, $form_state);
    $this->assertTrue($this->iefSubmitCalled);

    // No similar items where chosen, keep the existing entity.
    $this->assertEquals(123, $form_state->get([
      'entity_browser',
      'selected_entities',
      0,
    ])->id());

    // When a user chooses a similar item, make sure that's the one that will
    // be submit.
    $this->addDuplicationValidationService();
    $this->returnSimilarItems = TRUE;
    $form_state->setValue(['similar_items', 123, 'similar_selection'], 4);
    $this->plugin->submit($element, $form, $form_state);

    $this->assertNotEquals(123, $form_state->get([
      'entity_browser',
      'selected_entities',
      0,
    ])->id());
  }

  /**
   * Simulated Inline Entity Form submit.
   */
  public function iefElementSubmit() {
    $this->iefSubmitCalled = TRUE;
  }

  /**
   * Media duplication get similar entities callback.
   *
   * @return array
   *   Array of mock media entities.
   */
  public function getSimilarEntitiesCallback() {
    if (!$this->returnSimilarItems) {
      return [];
    }
    return [
      $this->getMockMediaEntity(),
      $this->getMockMediaEntity(),
    ];
  }

}

/**
 * Plugin class override to return expected results.
 */
class TestEmbedCode extends EmbedCode {

  /**
   * {@inheritDoc}
   */
  protected function getRenderDisplay(array &$render_array) {
    return 'foo bar';
  }

}
