<?php

namespace Drupal\stanford_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hook implementations for stanford_media.
 */
class StanfordMediaViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['entity_usage']['link'] = [
      'title' => $this->t('Usage Link'),
      'help' => $this->t('Link to the referencing entity.'),
      'field' => [
        'id' => 'entity_usage_link',
      ],
    ];
    $data['entity_usage']['source_id'] = [
      'title' => $this->t('Usage Source ID'),
      'help' => $this->t('The Entity Source ID using the entity.'),
      'field' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];
  }

}
