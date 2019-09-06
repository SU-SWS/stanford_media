<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\SelectionDisplay;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay\MultiStepDisplay;

/**
 * Override multistep display plugin to provide cardinality validation..
 *
 * @EntityBrowserSelectionDisplay(
 *   id = "multi_step_display",
 *   label = @Translation("Multi step selection display"),
 *   description = @Translation("Shows the current selection display, allowing
 *   to mix elements selected through different widgets in several steps."),
 *   acceptPreselection = TRUE, js_commands = TRUE
 * )
 */
class MultiStepSelection extends MultiStepDisplay {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state) {
    if (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] !== 'ajax_commands_handler') {
      // If the triggering item is a widget selector (via the tabs), we want to
      // clear the selections and anything we started with.
      if (in_array('widget_selector', $triggering_element['#array_parents'])) {
        $form_state->set([
          'entity_browser',
          'selected_entities',
        ], []);

        $form_state->set('dropzonejs', NULL);
        $form_state->set('embed_code', NULL);
      }
    }

    $original_form['#attributes']['class'][] = Html::cleanCssIdentifier($this->getPluginId());
    if (isset($original_form['widget']['view'])) {
      $original_form['#attributes']['class'][] = 'view';
      $original_form['#attributes']['class'] = array_values(array_unique(array_filter($original_form['#attributes']['class'])));
    }
    $form = parent::getForm($original_form, $form_state);

    $this->changeFormDisplay($form, $form_state);
    $form['#attached']['library'][] = 'stanford_media/multi_step';
    $form['selected']['message'] = [
      '#prefix' => '<div id="message">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];
    return $form;
  }

  /**
   * Alter the form and add the media labels.
   *
   * @param array $form
   *   Form to change.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function changeFormDisplay(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->get([
      'entity_browser',
      'selected_entities',
    ]);

    foreach ($selected_entities as $id => $entity) {
      $form['selected']['items_' . $entity->id() . '_' . $id]['filename'] = [
        '#markup' => $entity->label(),
        '#prefix' => '<div class="filename">',
        '#suffix' => '</div>',
        '#weight' => 99,
      ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * Alters parent method to validate cardinality during ajax.
   */
  protected function executeJsCommand(FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $commands = json_decode($triggering_element['#value'], TRUE);

    // Process Remove command.
    if (isset($commands['remove'])) {
      $entity_ids = $commands['remove'];

      // Remove weight of entity being removed.
      foreach ($entity_ids as $entity_info) {
        $entity_id_info = explode('_', $entity_info['entity_id']);

        $form_state->unsetValue([
          'selected',
          $entity_info['entity_id'],
        ]);

        // Remove entity itself.
        $selected_entities = &$form_state->get([
          'entity_browser',
          'selected_entities',
        ]);
        unset($selected_entities[$entity_id_info[2]]);
      }

      static::saveNewOrder($form_state);
    }

    // Process Add command.
    if (isset($commands['add'])) {
      $entity_ids = $commands['add'];

      $entities_to_add = [];
      $added_entities = [];

      // Generate list of entities grouped by type, to speed up loadMultiple.
      foreach ($entity_ids as $entity_pair_info) {
        $entity_info = explode(':', $entity_pair_info['entity_id']);

        if (!isset($entities_to_add[$entity_info[0]])) {
          $entities_to_add[$entity_info[0]] = [];
        }

        $entities_to_add[$entity_info[0]][] = $entity_info[1];
      }

      // Load Entities and add into $added_entities, so that we have list of
      // entities with key - "type:id".
      foreach ($entities_to_add as $entity_type => $entity_type_ids) {
        $indexed_entities = $this->entityTypeManager->getStorage($entity_type)
          ->loadMultiple($entity_type_ids);

        foreach ($indexed_entities as $entity_id => $entity) {
          $added_entities[implode(':', [
            $entity_type,
            $entity_id,
          ])] = $entity;
        }
      }

      // Array is accessed as reference, so that changes are propagated.
      $selected_entities = &$form_state->get([
        'entity_browser',
        'selected_entities',
      ]);

      // Fill list of selected entities in correct order with loaded entities.
      // In this case, order is preserved and multiple entities with same ID
      // can be selected properly.
      foreach ($entity_ids as $entity_pair_info) {
        $selected_entities[] = $added_entities[$entity_pair_info['entity_id']];
      }

      $within_card = self::checkCardinality($form_state, $selected_entities);
      $form_state->set('process_ajax', $within_card);
    }
  }

  /**
   * Trim the number of entities and return IF they have been trimmed.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\media\Entity\Media[] $entities
   *   Selected entities.
   *
   * @return bool
   *   True if the number of entities is allowed with the cardinality.
   */
  protected static function checkCardinality(FormStateInterface $form_state, array &$entities = []) {
    $original_count = count($entities);
    $cardinality = self::getCardinality($form_state);
    if ($cardinality >= 1) {
      $entities = array_slice($entities, 0, $cardinality);
      return $original_count == count($entities);
    }
    return TRUE;
  }

  /**
   * Handler to generate Ajax response, after command is executed.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax response with commands.
   */
  public static function handleAjaxCommand(array $form, FormStateInterface $form_state) {
    if ($form_state->get('process_ajax')) {
      $ajax = parent::handleAjaxCommand($form, $form_state);
      $ajax->addCommand(new ReplaceCommand('div[id="message"]', "<div id=\"message\"></div>"));
      return $ajax;
    }
    $cardinality = self::getCardinality($form_state);
    $ajax = new AjaxResponse();

    $message = \Drupal::translation()
      ->translate('Only %cardinality items can be used.', ['%cardinality' => $cardinality])
      ->render();
    if ($cardinality == 1) {
      $message = \Drupal::translation()
        ->translate('Only %cardinality item can be used.', ['%cardinality' => $cardinality])
        ->render();
    }
    $ajax->addCommand(new ReplaceCommand('div[id="message"]', "<div id=\"message\"><div class=\"messages messages--error\">$message</div></div>"));
    return $ajax;
  }

  /**
   * Get the cardinality of the widget.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return int
   *   Cardinality.
   */
  protected static function getCardinality(FormStateInterface $form_state) {
    return (int) $form_state->get([
      'entity_browser',
      'validators',
      'cardinality',
      'cardinality',
    ]);
  }

}
