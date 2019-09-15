<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes embedded image media items.
 *
 * @MediaEmbedDialog(
 *   id = "image"
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
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    $input = ['image_style' => NULL];
    return $input + parent::getDefaultInput();
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);

    $default_value = NULL;
    $user_input = $form_state->getUserInput();

    if (!empty($user_input['editor_object']['attributes']['data-image-style'])) {
      $default_value = $user_input['editor_object']['attributes']['data-image-style'];
    }

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $this->getImageStyles(),
      '#default_value' => $default_value,
      '#empty_option' => $this->t('None (original image)'),
      '#weight' => -10,
    ];
  }

  /**
   * Get all available image styles.
   *
   * @return array
   *   Keyed array of image styles and their labels.
   */
  protected function getImageStyles() {
    $styles = image_style_options(FALSE);

    $allowed_styles = $this->configFactory->get('stanford_media.settings')
      ->get('embeddable_image_styles');

    return array_filter($styles, function ($style_id) use ($allowed_styles) {
      return empty($allowed_styles) || in_array($style_id, $allowed_styles);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state) {
    $values['attributes']['data-image-style'] = $form_state->getValue('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity) {
    parent::embedAlter($build, $entity);
    $source_field = static::getMediaSourceField($entity);
    if (!empty($build['#attributes']['data-image-style'])) {
      // Ensure a user can't put just anything in the html.
      if (array_key_exists($build['#attributes']['data-image-style'], $this->getImageStyles())) {
        $build[$source_field][0]['#theme'] = 'image_formatter';;
        $build[$source_field][0]['#image_style'] = $build['#attributes']['data-image-style'];
        $build['#cache']['keys'][] = $build['#attributes']['data-image-style'];
      }
      unset($build['#attributes']['data-image-style']);
    }
  }

}
