<?php

namespace Drupal\stanford_media\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AllowedStyles to change the embeddable image style options.
 *
 * @package Drupal\stanford_media\Form
 */
class AllowedStyles extends ConfigFormBase {

  /**
   * Used to get the image styles.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stanford_media_allowed_styles';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stanford_media.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stanford_media.settings');
    $form = parent::buildForm($form, $form_state);
    $form['allowed_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Image Styles'),
      '#options' => $this->getImageStyles(),
      '#default_value' => $config->get('embeddable_image_styles') ?: array_keys($this->getImageStyles()),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('stanford_media.settings');
    $allowed_styles = $form_state->getValue('allowed_styles');
    $allowed_styles = array_values($allowed_styles);
    $config->set('embeddable_image_styles', array_filter($allowed_styles));
    $config->save();
  }

  /**
   * Get all image styles.
   *
   * @return array
   *   Keyed array of image styles and their label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getImageStyles() {
    $styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple();

    $style_options = [];
    /** @var \Drupal\image\Entity\ImageStyle $style */
    foreach ($styles as $style) {
      $style_options[$style->id()] = $style->label();
    }
    asort($style_options);

    return $style_options;
  }

}
