<?php

namespace Drupal\media_duplicate_validation\Plugin\EntityBrowser\WidgetValidation;

use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\WidgetValidationBase;
use Drupal\file\Entity\File;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Validates that the widget returns the appropriate number of elements.
 *
 * @EntityBrowserWidgetValidation(
 *   id = "file_duplication",
 *   label = @Translation("File Duplication validator")
 * )
 */
class FileDuplication extends WidgetValidationBase {

  /**
   * Media Duplication Manager service.
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
      $container->get('typed_data_manager'),
      $container->get('plugin.manager.media_duplicate_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TypedDataManagerInterface $typed_data_manager, MediaDuplicateValidationManager $duplication_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $typed_data_manager);
    $this->duplicationManager = $duplication_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $entities, $options = []) {
    $violations = new ConstraintViolationList();

    /** @var \Drupal\media\MediaInterface $entity */
    foreach ($entities as $entity) {
      $file = File::load($entity->getSource()->getSourceFieldValue($entity));
      if (!$file) {
        continue;
      }

      foreach ($this->duplicationManager->getDefinitions() as $definition) {
        /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
        $plugin = $this->duplicationManager->createInstance($definition['id']);

        if (!$plugin->isUnique($file->getFileUri())) {
          $message = $this->t('This file already exists');
          $violation = new ConstraintViolation($message, $message, [], '', '', '');
          $violations->add($violation);
          $entity->delete();
        }
      }
    }

    return $violations;
  }

}
