<?php

namespace Drupal\stanford_media\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MediaBrowserBase for our entity browser widgets.
 *
 * @package Drupal\stanford_media\Plugin\EntityBrowser\Widget
 */
abstract class MediaBrowserBase extends WidgetBase {

  /**
   * Finds which media type is appropriate.
   *
   * @var \Drupal\stanford_media\Plugin\BundleSuggestionManagerInterface
   */
  protected $bundleSuggestion;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Sets user messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('plugin.manager.bundle_suggestion_manager'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestionManagerInterface $bundle_suggestion, AccountProxyInterface $current_user, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->bundleSuggestion = $bundle_suggestion;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['form_mode'] = 'media_browser';
    return $configuration;
  }

  /**
   * Returns the bundles that this widget may use.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string[]
   *   The bundles that this widget may use. If all bundles may be used, the
   *   returned array will be empty.
   */
  protected function getAllowedBundles(FormStateInterface $form_state) {
    return (array) $form_state->get([
      'entity_browser',
      'widget_context',
      'target_bundles',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $widget_params) {
    $form = parent::getForm($original_form, $form_state, $widget_params);
    $original_form['#attributes']['class'][] = Html::cleanCssIdentifier($this->id());
    $this->getEntityForm($form, $form_state, $widget_params);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    parent::submit($element, $form, $form_state);

    // Execute any Inline Entity Form submit functions on each entity.
    // This will save any changes such as media title etc.
    $children = Element::children($element['entities']);
    foreach ($children as $child) {
      $entity_form = $element['entities'][$child]['entity_form'];

      if (!isset($entity_form['#ief_element_submit'])) {
        continue;
      }

      foreach ($entity_form['#ief_element_submit'] as $submit_function) {
        call_user_func_array($submit_function, [&$entity_form, $form_state]);
      }
    }

    $media_entities = $this->prepareEntities($form, $form_state);
    $this->cleanDuplicates($element, $form, $form_state);
    if (empty($form_state->get(['entity_browser', 'selected_entities']))) {
      $this->selectEntities($media_entities, $form_state);
    }
  }

  /**
   * Add the inline entity form after the files have been uploaded.
   *
   * @param array $form
   *   Original form from getFrom().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $widget_params
   *   Additional parameters we dont need.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getEntityForm(array &$form, FormStateInterface $form_state, array $widget_params) {
    if (isset($form['actions'])) {
      $form['actions']['#weight'] = 100;
    }

    $form['entities'] = [
      '#prefix' => '<div id="entities">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    $media_entities = $this->prepareEntities($form, $form_state);

    // No entities to create forms/previews for.
    if (empty($media_entities)) {
      $form['entities']['#markup'] = NULL;
      return;
    }

    unset($form['actions']);

    // Build the entity form.
    foreach ($media_entities as $entity) {
      $labels[] = $entity->label();

      $form['entities'][$entity->id()]['entity_form'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#default_value' => $entity,
        '#form_mode' => 'media_browser',
      ];
      $form['entities'][$entity->id()] += $this->getSimilarForm($entity);
    }

    // Prompt the user of a successful addition.
    if (!empty($labels)) {
      $this->messenger->addMessage($this->t('%name has been added to the media library', ['%name' => implode(', ', $labels)]));
    }
  }

  /**
   * Build the inline enitity form and if applicable, similar entity selection.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   Entity for the inline form.
   *
   * @return array
   *   Form array for the given entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getSimilarForm(MediaInterface $entity) {
    $form = [];

    $duplication_manager = self::getDuplicationManager();
    $similar_media = $duplication_manager ? $duplication_manager->getSimilarEntities($entity, 3) : [];
    if (empty($similar_media)) {
      return [];
    }

    $form['similar_items'] = [
      '#type' => 'details',
      '#title' => $this->t('Similar Items'),
      '#description' => $this->t('We see that a similar item already exists in the Media Library.'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#weight' => -99,
      '#attributes' => ['class' => ['similar-items-wrapper']],
    ];
    $form['similar_items'][$entity->id()]['similar_selection'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select and use an existing item below, or continue to add your new file.'),
      '#description' => $this->t('To prevent duplication, perhaps one of these existing items might work.'),
      '#options' => $this->getRadioOptions($similar_media) + [$this->t('Add new')],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Get the media duplication manager service if its available.
   *
   * @return \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager|null
   *   Duplication manager service.
   */
  protected static function getDuplicationManager() {
    if (\Drupal::hasService('plugin.manager.media_duplicate_validation')) {
      return \Drupal::service('plugin.manager.media_duplicate_validation');
    }
  }

  /**
   * Get an keyed array that can be used in form api as a radio button options.
   *
   * @param \Drupal\media\MediaInterface[] $entities
   *   Array of media entities.
   *
   * @return array
   *   Array of keyed values for radio buttons.
   */
  protected function getRadioOptions(array $entities) {
    $media_view_builder = $this->entityTypeManager->getViewBuilder('media');
    $options = [];
    foreach ($entities as $media) {
      $media_display = $media_view_builder->view($media, 'preview');
      $options[$media->id()] = '<div class="media-label label">';
      $options[$media->id()] .= $this->t('Use %name', ['%name' => $media->label()])
        ->render();
      $options[$media->id()] .= '</div>';
      $options[$media->id()] .= $this->getRenderDisplay($media_display);
    }
    return $options;
  }

  /**
   * Get the rendered result of a render array.
   *
   * @param array $render_array
   *   Entity render array.
   *
   * @return string
   *   Rendered contents.
   *
   * @codeCoverageIgnore
   */
  protected function getRenderDisplay(array &$render_array){
    return render($render_array);
  }

  /**
   * Select the entities the user wants if they selected to use existing.
   *
   * @param array $element
   *   Form element array.
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function cleanDuplicates(array &$element, array &$form, FormStateInterface $form_state) {
    $selected_items = $form_state->get(['entity_browser', 'selected_entities']);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($selected_items as $delta => $entity) {
      $chosen_id = $form_state->getValue([
        'similar_items',
        $entity->id(),
        'similar_selection',
      ]);

      if ($chosen_id) {
        $entity->delete();
        $selected_items[$delta] = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
          ->load($chosen_id);
      }
    }

    $form_state->set(['entity_browser', 'selected_entities'], $selected_items);
  }

  /**
   * Build a media entity using the given media type and source data.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   Media type to create entity in.
   * @param mixed $source_value
   *   Files, string or other to put into the source field.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created media entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function prepareMediaEntity(MediaTypeInterface $media_type, $source_value) {
    $media_storage = $this->entityTypeManager->getStorage('media');

    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    $entity_data = [
      'bundle' => $media_type->id(),
      $source_field => is_array($source_value) ? $source_value : [$source_value],
      'uid' => $this->currentUser->id(),
      'status' => TRUE,
      'type' => $media_type->getSource()->getPluginId(),
    ];
    if (is_string($source_value)) {
      $entity_data['name'] = $this->bundleSuggestion->getSuggestedName($source_value);
    }

    return $media_storage->create($entity_data);
  }

}
