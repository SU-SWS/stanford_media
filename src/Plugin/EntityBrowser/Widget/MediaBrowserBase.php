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
use Drupal\file\Entity\File;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Drupal\stanford_media\BundleSuggestion;
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
   * @var \Drupal\stanford_media\BundleSuggestion
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
   * Media duplicate validation manager service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

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
      $container->get('stanford_media.bundle_suggestion'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('plugin.manager.media_duplicate_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BundleSuggestion $bundles, AccountProxyInterface $current_user, MessengerInterface $messenger, MediaDuplicateValidationManager $duplication_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->bundleSuggestion = $bundles;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->duplicationManager = $duplication_manager;
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
      $form['entities'][$entity->id()] = $this->buildEntityPreview($entity);
    }

    // Prompt the user of a successful addition.
    if (!empty($labels)) {
      $this->messenger->addMessage($this->t('%name has been added to the media library', ['%name' => implode(', ', $labels)]));
    }

    // Without this, IEF won't know where to hook into the widget. Don't pass
    // $original_form as the second argument to addCallback(), because it's not
    // just the entity browser part of the form, not the actual complete form.
    ElementSubmit::addCallback($form['actions']['submit'], $form_state->getCompleteForm());
  }

  /**
   * @param \Drupal\media\MediaInterface $entity
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildEntityPreview(MediaInterface $entity) {
    $preview = [];
    $preview['entity_form'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => $entity->getEntityTypeId(),
      '#bundle' => $entity->bundle(),
      '#default_value' => $entity,
      '#form_mode' => 'media_browser',
    ];

    $similar_media = [];
    $media_view_builder = $this->entityTypeManager->getViewBuilder('media');

    foreach ($this->duplicationManager->getDefinitions() as $definition) {

      /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
      $plugin = $this->duplicationManager->createInstance($definition['id']);
      $similar_media = array_merge($similar_media, $plugin->getSimilarItems($entity));

      if (empty($similar_media)) {
        continue;
      }

      $this->messenger->addWarning($this->t('Similar items exist for file %name', ['%name' => $entity->label()]));
      $similar_choices = [$this->t('Add new')];

      foreach (array_slice($similar_media, 0, 3) as $media) {
        $media_display = $media_view_builder->view($media, 'preview');
        $similar_choices[$media->id()] = '<div class="media-label label">';
        $similar_choices[$media->id()] .= $this->t('Use %name', ['%name' => $media->label()])
          ->render();
        $similar_choices[$media->id()] .= '</div>';
        $similar_choices[$media->id()] .= render($media_display);
      }
      $preview['similar_items'] = [
        '#type' => 'details',
        '#title' => $this->t('Similar Items'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#weight' => -99,
      ];
      $preview['similar_items'][$entity->id()]['similar_selection'] = [
        '#type' => 'radios',
        '#title' => $this->t('Use an existing item instead'),
        '#options' => $similar_choices,
        '#required' => TRUE,
        '#original_id' => $entity->id(),
      ];

    }

    return $preview;
  }

  /**
   * @param array $element
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function cleanDuplicates(array &$element, array &$form, FormStateInterface $form_state) {
    $similar_choices = $form_state->getValue('similar_items', []);
    $selected_items = [];
    foreach ($similar_choices as $original_id => $selection) {
      $selected_items[$original_id] = Media::load($original_id);

      if ($selection['similar_selection']) {
        $selected_items[$original_id]->delete();
        unset($selected_items[$original_id]);
        $selected_items[$selection['similar_selection']] = Media::load($selection['similar_selection']);
      }
    }
    $form_state->set(['entity_browser', 'selected_entities'], $selected_items);
  }

  /**
   * Build a media entity using the given media type and source data.
   *
   * @param \Drupal\media\Entity\MediaType $media_type
   *   Media type to create entity in.
   * @param mixed $source_value
   *   Files, string or other to put into the source field.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created media entity.
   */
  protected function prepareMediaEntity(MediaType $media_type, $source_value) {
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

    return $media_storage->create($entity_data);
  }

}
