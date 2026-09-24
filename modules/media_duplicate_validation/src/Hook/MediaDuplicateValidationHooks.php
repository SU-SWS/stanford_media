<?php

namespace Drupal\media_duplicate_validation\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for media_duplicate_validation.
 */
class MediaDuplicateValidationHooks {

  use StringTranslationTrait;

  /**
   * Hooks constructor.
   *
   * @param \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager $duplicateManager
   *   Media duplicate validation plugin manager.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.media_duplicate_validation')]
    protected MediaDuplicateValidationManager $duplicateManager,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name == 'help.page.media_duplicate_validation') {
      $output = '<h3>' . $this->t('About') . '</h3>';
      $output .= '<p>' . $this->t('Media Validation plugins to help prevent duplication of media items') . '</p>';
      return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules): void {
    $this->duplicateManager->buildPluginSchemas();
  }

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall(string $module): void {
    foreach ($this->duplicateManager->getDefinitions() as $definition) {
      if ($definition['provider'] == $module) {
        $this->duplicateManager->removeSchemas($definition['id']);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * Perform any necessary actions when a media entity is saved for each plugin.
   */
  #[Hook('media_insert')]
  #[Hook('media_update')]
  public function mediaSave(EntityInterface $entity): void {
    foreach ($this->duplicateManager->getDefinitions() as $definition) {
      /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
      $plugin = $this->duplicateManager->createInstance($definition['id']);
      $plugin->mediaSave($entity);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   *
   * Perform any necessary actions when a media entity is deleted for each
   * plugin.
   */
  #[Hook('media_delete')]
  public function mediaDelete(EntityInterface $entity): void {
    foreach ($this->duplicateManager->getDefinitions() as $definition) {
      /** @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationInterface $plugin */
      $plugin = $this->duplicateManager->createInstance($definition['id']);
      $plugin->mediaDelete($entity);
    }
  }

}
