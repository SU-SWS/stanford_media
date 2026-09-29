<?php

/**
 * @file
 * stanford_media.post_update.php
 */

/**
 * Implements hook_removed_post_updates().
 */
function stanford_media_removed_post_updates() {
  return [
    'stanford_media_post_update_8200' => '9.0.0',
    'stanford_media_post_update_8201' => '9.0.0',
    'stanford_media_post_update_8202' => '9.0.0',
    'stanford_media_post_update_8203' => '9.0.0',
    'stanford_media_post_update_8204' => '9.0.0',
  ];
}

/**
 * Update entity_usage settings to enable the tab and warnings.
 */
function stanford_media_post_update_11000() {
  $config = \Drupal::configFactory()->getEditable('entity_usage.settings');
  $local_tasks = $config->get('local_task_enabled_entity_types') ?: [];
  if (!in_array('media', $local_tasks)) {
    $local_tasks[] = 'media';
    $config->set('local_task_enabled_entity_types', $local_tasks);
  }

  $warning = $config->get('edit_warning_message_entity_types');
  if (!in_array('media', $warning)) {
    $warning[] = 'media';
    $config->set('edit_warning_message_entity_types', $warning);
  }

  $warning = $config->get('delete_warning_message_entity_types');
  if (!in_array('media', $warning)) {
    $warning[] = 'media';
    $config->set('delete_warning_message_entity_types', $warning);
  }
  $config->save();
}
