<?php

/**
 * @file
 * stanford_media.views.inc
 */

/**
 * Implements hook_views_data_alter().
 */
function stanford_media_views_data_alter(array &$data) {
  $data['entity_usage']['link'] = [
    'title' => t('Usage Link'),
    'help' => t('Link to the referencing entity.'),
    'field' => [
      'id' => 'entity_usage_link',
    ],
  ];

  return $data;
}
