<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\linkit\Element\Linkit;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes embedded image media items.
 *
 * @MediaEmbedDialog(
 *   id = "image",
 *   media_type = "image"
 * )
 */
class Image extends MediaEmbedDialogBase {

  /**
   * Used to get the config for allowed image styles.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger factory service.
   *
   * Can't use a channel in this object due to serialization issues.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->bundle() == 'image';
    }
    return FALSE;
  }

  /**
   * Use Linkit functions but replace the autocomplete library with our own.
   *
   * {@inheritdoc}
   *
   * @see Linkit::processLinkitAutocomplete()
   */
  public function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    Linkit::processLinkitAutocomplete($element, $form_state, $complete_form);
    // Replace linkit autocomplete library with our own to fix some nasty bugs.
    $element['#attached']['library'] = ['stanford_media/autocomplete'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    $input = [
      'image_style' => NULL,
      'alt_text' => NULL,
      'title_text' => NULL,
      'linkit' => [],
    ];
    return $input + parent::getDefaultInput();
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $input = $this->getUserInput($form_state);

    $attribute_settings = &$form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY];

    // Image style options.
    $attribute_settings['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $this->getImageStyles(),
      '#default_value' => $input['image_style'],
      '#empty_option' => $this->t('None (original image)'),
    ];

    // Change textfield into text format for more robust captions.
    if (isset($form['attributes']['data-caption'])) {
      $caption_field = $form['attributes']['data-caption'];
      $caption_field['#type'] = 'text_format';

      $default_caption = $this->getCaptionDefault($form, $form_state);

      $caption_field['#default_value'] = $default_caption['value'] ?? '';
      $caption_field['#format'] = $default_caption['format'] ?? NULL;
      $caption_field['#description'] = $this->t('Enter information about this image to credit owner or to provide additional context.');
      unset($caption_field['#element_validate']);

      $format_config = $this->configFactory->get('stanford_media.allowed_caption_formats');
      if ($allowed_formats = $format_config->get('allowed_formats')) {
        $caption_field['#allowed_formats'] = $allowed_formats;
        $caption_field['#format'] = $caption_field['#format'] ?: reset($caption_field['#allowed_formats']);
      }

      $attribute_settings['caption'] = $caption_field;
      unset($form['attributes']['data-caption']);
    }

    try {
      $this->buildLinkitField($form, $form_state);
    }
    catch (\Exception $e) {
      // No link field, no need for title text field.
      return;
    }
    $attribute_settings['title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title Text'),
      '#description' => $this->t('Please describe what the link above leads to. (ie. Stanford University Home Page'),
      '#default_value' => $input['title_text'] ?: '',
      '#weight' => 199,
      '#states' => [
        'visible' => [
          'input[name*="[linkit][href]"]' => ['filled' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Get the default caption data either from the form or the users input.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Keyed array of caption data from a text_format field.
   */
  protected function getCaptionDefault(array $form, FormStateInterface $form_state) {
    if (!empty($form['attributes']['data-caption']['#default_value'])) {
      return json_decode($form['attributes']['data-caption']['#default_value'], TRUE);
    }

    $user_input = $form_state->getUserInput();
    if (!empty($user_input['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['caption'])) {
      return $user_input['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['caption'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateDialogForm(array &$form, FormStateInterface $form_state) {
    parent::validateDialogForm($form, $form_state);

    $caption_path = [
      'attributes',
      MediaEmbedDialogInterface::SETTINGS_KEY,
      'caption',
    ];
    $caption = $form_state->getValue($caption_path);
    // Clear the caption values from the container. We'll set the data-caption
    // value later so that core can handle the caption stuff.
    $form_state->unsetValue($caption_path);

    if (!$caption) {
      return;
    }

    if (!empty($caption['value'])) {
      // Clean up the caption and escape special characters so it can be used in
      // the json string.
      $form_state->setValue([
        'attributes',
        'data-caption',
      ], htmlspecialchars(json_encode($caption)));
    }
  }

  /**
   * Get all available image styles.
   *
   * @return array
   *   Keyed array of image styles and their labels.
   */
  protected function getImageStyles() {
    try {
      $styles = $this->entityTypeManager->getStorage('image_style')
        ->loadMultiple();
    }
    catch (\Exception $e) {
      return [];
    }

    // If we have a config file that limits the image styles, lets use only
    // those. Otherwise we'll use all styles.
    $config = $this->configFactory->get('stanford_media.embeddable_image_styles');
    $allowed_styles = $config->get('allowed_styles') ?: array_keys($styles);

    $style_options = [];
    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      if (in_array($style->id(), $allowed_styles)) {
        $style_options[$style->id()] = $style->label();
      }
    }
    asort($style_options);

    return $style_options;
  }

  /**
   * Adds a linkit field to the form.
   *
   * @param array $form
   *   Embed dialog form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Embed dialog form state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildLinkitField(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
    $filter_format = $form_state->getBuildInfo()['args'][0];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->entityTypeManager->getStorage('editor')
      ->load($filter_format->id());
    $plugin_settings = $editor->getSettings()['plugins']['drupallink'];

    // Do not alter the form if Linkit is not enabled for this text format.
    if (!isset($plugin_settings['linkit_enabled']) || (isset($plugin_settings['linkit_enabled']) && !$plugin_settings['linkit_enabled'])) {
      return;
    }

    $linkit_input = $this->getUserInput($form_state)['linkit'];
    $linkit_profile_id = $editor->getSettings()['plugins']['drupallink']['linkit_profile'];

    $link_form = [
      '#title' => $this->t('Add Link to image'),
      '#type' => 'textfield',
      '#description' => $this->t('If you would like to make this image a link, enter the url here. Or start typing to find content.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      '#default_value' => isset($linkit_input['href']) ? $linkit_input['href'] : '',
      '#states' => [
        'visible' => [
          '[name="attributes[data-entity-embed-display-settings][image_link]"]' => ['value' => 'url'],
        ],
      ],
      '#process' => [[$this, 'processLinkitAutocomplete']],
      '#element_validate' => [[$this, 'validateLinkitHref']],
    ];

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit'] = [
      '#type' => 'container',
      '#weight' => 99.5,
    ];

    $fields = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit']['href_dirty_check'] = [
      '#type' => 'hidden',
      '#default_value' => $linkit_input['href'] ?? '',
    ];

    $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit']['href'] = $link_form;
    foreach ($fields as $field) {
      $form['attributes'][MediaEmbedDialogInterface::SETTINGS_KEY]['linkit'][$field] = [
        '#title' => $field,
        '#type' => 'hidden',
        '#default_value' => isset($linkit_input[$field]) ? $linkit_input[$field] : '',
      ];
    }
  }

  /**
   * Validate the provided link string is valid.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $form
   *   Complete form.
   */
  public function validateLinkitHref(array &$element, FormStateInterface $form_state, array &$form) {
    if (!empty($element['#value'])) {
      try {
        // If getting the link object fails, tell the user the path they
        // provided is invalid.
        self::getLinkObject($element['#value']);
      }
      catch (\Exception $e) {
        $form_state->setError($element, 'Invalid link');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitDialogForm(array &$form, FormStateInterface $form_state) {
    parent::submitDialogForm($form, $form_state);

    $settings = $form_state->getValue([
      'attributes',
      'data-entity-embed-display-settings',
    ]);
    $settings = array_filter($settings);
    // Add a simple placeholder. This just prevents the settings from being an
    // empty string. An empty string causes php notices when displaying.
    if (!isset($settings['image_style']) && empty($settings['linkit']['href'])) {
      $settings['place'] = 1;
    }

    // Similarly to the placeholder above, ensure the alt key is populated to
    // prevent undefined index notices.
    $settings['alt'] = $settings['alt'] ?? '';

    $form_state->setValue([
      'attributes',
      'data-entity-embed-display-settings',
    ], $settings);

    $linkit_key = [
      'attributes',
      'data-entity-embed-display-settings',
      'linkit',
    ];
    $linkit_settings = $form_state->getValue($linkit_key);
    $href = $linkit_settings['href'];
    // No link: unset values to clean up the embed code.
    if (!$href) {
      $form_state->unsetValue($linkit_key);
      return;
    }

    $href_dirty_check = $linkit_settings['href_dirty_check'];

    // Unset the attributes since this is an external url.
    if (!$href || $href !== $href_dirty_check) {
      unset($linkit_settings['data-entity-type']);
      unset($linkit_settings['data-entity-uuid']);
      unset($linkit_settings['data-entity-substitution']);
    }

    unset($linkit_settings['href_dirty_check']);

    $form_state->setValue($linkit_key, array_filter($linkit_settings));
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $element['#media'];
    $source_field = static::getMediaSourceField($entity);

    if (!empty($element['#display_settings']['title_text'])) {
      $element[$source_field][0]['#item_attributes']['title'] = $element['#display_settings']['title_text'];
    }

    if (!empty($element['#display_settings']['linkit'])) {
      $link_path = $element['#display_settings']['linkit']['href'];
      $link_options = ['attributes' => $element['#display_settings']['linkit']];
      unset($element['#display_settings']['linkit']['href']);

      // Protect issues when a user might have modified the markup without going
      // through the dialog to encounter the form validation.
      try {
        $element[$source_field][0]['#url'] = self::getLinkObject($link_path, $link_options);
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('stanford_media')->error($e->getMessage());
      }

    }
    $this->setElementImageStyle($element, $source_field);

    $media_type = MediaType::load($entity->bundle());
    // Caption is provided in another caption entry from the wysiwyg.
    $field_map = $media_type->getFieldMap();
    if (isset($field_map['caption'])) {
      unset($element[$field_map['caption']]);
    }
    return $element;
  }

  /**
   * Get a Url link object from the given path.
   *
   * @param string $link_path
   *   Url path.
   * @param array $link_options
   *   Url options, see Url::fromUri().
   *
   * @return \Drupal\Core\Url
   *   Constructed url object.
   */
  protected static function getLinkObject($link_path, array $link_options = []) {
    try {
      // Local paths.
      return Url::fromUserInput($link_path, $link_options);
    }
    catch (\Exception $e) {
      // External paths.
      return Url::fromUri($link_path, $link_options);
    }
  }

  /**
   * Set the image style as appropriate for the render element.
   *
   * @param array $element
   *   Render array element.
   * @param string $source_field
   *   Source field machine name.
   */
  protected function setElementImageStyle(array &$element, $source_field) {
    if (!empty($element['#display_settings']['image_style'])) {
      $element[$source_field]['#formatter'] = 'image';
      $element[$source_field][0]['#theme'] = 'image_formatter';;
      $element[$source_field][0]['#image_style'] = $element['#display_settings']['image_style'];
    }
    else {
      unset($element[$source_field][0]['#image_style']);
    }
  }

}
