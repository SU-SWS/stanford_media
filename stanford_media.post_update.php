<?php

/**
 * @file
 * stanford_media.post_update.php
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Template\Attribute;
use Drupal\editor\Entity\Editor;
use Drupal\embed\Entity\EmbedButton;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\MediaType;
use Drupal\views\Entity\View;
use Drupal\image\Entity\ImageStyle;

/**
 * Convert media browser field widgets to media library widgets.
 */
function stanford_media_post_update_8200() {
  \Drupal::service('module_installer')->install(['media_library']);

  if (!FieldStorageConfig::load('media.field_media_oembed_video')) {
    FieldStorageConfig::create([
      'field_name' => 'field_media_oembed_video',
      'entity_type' => 'media',
      'type' => 'string',
    ])->save();
  }
  if (!FieldConfig::load('media.video.field_media_oembed_video')) {
    FieldConfig::create([
      'field_name' => 'field_media_oembed_video',
      'label' => 'Video URL',
      'entity_type' => 'media',
      'bundle' => 'video',
      'required' => TRUE,
    ])->save();
  }

  $media_library_widget = ['type' => 'media_library_widget'];

  /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
  foreach (EntityFormDisplay::loadMultiple() as $form_display) {
    $form_modified = FALSE;

    // Change all media reference field widgets to use the media library widget.
    foreach ($form_display->getComponents() as $component_name => $settings) {
      if (isset($settings['type']) && $settings['type'] == 'entity_browser_entity_reference') {
        $media_library_widget['weight'] = $settings['weight'];
        $form_display->setComponent($component_name, $media_library_widget);
        $form_modified = TRUE;
      }
    }

    // Hide the old field, and add the new field to the form display.
    if ($form_display->getTargetEntityTypeId() == 'media' && $form_display->getTargetBundle() == 'video') {
      $form_display->removeComponent('field_media_video_embed_field');
      $form_display->setComponent('field_media_oembed_video');
      $form_modified = TRUE;
    }

    if ($form_modified) {
      $form_display->save();
    }
  }

  foreach (EntityViewDisplay::loadMultiple() as $view_display) {
    if ($view_display->getTargetEntityTypeId() == 'media' && $view_display->getTargetBundle() == 'video') {
      $view_display->removeComponent('field_media_video_embed_field');
      $view_display->setComponent('field_media_oembed_video', [
        'type' => 'oembed',
        'label' => 'hidden',
      ]);
      $view_display->save();
    }
  }

  drupal_flush_all_caches();

  $source_config = [
    'source_field' => 'field_media_oembed_video',
    'providers' => ['YouTube', 'Vimeo'],
    'thumbnails_directory' => 'public://oembed_thumbnails',
  ];
  /** @var \Drupal\media\MediaTypeInterface $media_type */
  $media_type = MediaType::load('video');
  $media_type->set('source_configuration', $source_config);
  $media_type->set('source', 'oembed:video');
  $media_type->calculateDependencies();
  $media_type->save();

}

/**
 * Update text formats by switching the media buttons.
 */
function stanford_media_post_update_8201() {

  /** @var \Drupal\editor\EditorInterface $editor */
  foreach (Editor::loadMultiple() as $editor) {

    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as &$row_items) {
      foreach ($row_items as &$row_group) {
        if ($media_browser_position = array_search('media_browser', $row_group['items'])) {
          $row_group['items'][$media_browser_position] = 'DrupalMediaLibrary';
          $editor->setSettings($settings);
          $editor->save();
        }
      }
    }
  }

  $display_modes = ['full'];
  $media_settings = \Drupal::configFactory()
    ->getEditable('stanford_media.settings');
  foreach ($media_settings->get('embeddable_image_styles') as $image_style) {
    $display_modes[] = _stanford_media_post_update_8202_image_display_mode($image_style);
  }
  $display_modes = array_unique($display_modes);
  $media_settings->delete();

  /** @var \Drupal\filter\FilterFormatInterface $filter */
  foreach (FilterFormat::loadMultiple() as $filter) {
    $filters = $filter->get('filters');
    if (isset($filters['entity_embed'])) {
      $filters['media_embed'] = [
        'id' => 'media_embed',
        'provider',
        'media',
        'status' => TRUE,
        'weight' => $filters['entity_embed']['weight'],
        'settings' => [
          'default_view_mode' => 'full',
          'allowed_view_modes' => array_combine($display_modes, $display_modes),
        ],
      ];
      // Make sure if the filter strips html tags, we need to keep the
      // drupal-media tag and its attributes.
      if (isset($filters['filter_html'])) {
        $filters['filter_html']['settings']['allowed_html'] .= ' <drupal-media data-entity-type data-entity-uuid data-align data-caption data-* alt>';
      }
      $filter->set('filters', $filters);
      $filter->save();
    }
  }
}

/**
 * Convert drupal-entity tags in wysiwyg's to drupal-media tags.
 */
function stanford_media_post_update_8202(&$sandbox) {
  if (!isset($sandbox['count'])) {
    $sandbox['entities'] = _stanford_media_post_update_8202_entity_list();
    $sandbox['count'] = count($sandbox['entities']);
  }

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_ids = array_splice($sandbox['entities'], 0, 25);
  foreach ($entity_ids as $item) {
    list($entity_type, $field_name, $entity_id) = explode(':', $item);

    $entity = $entity_type_manager->getStorage($entity_type)->load($entity_id);
    $field_values = $entity->get($field_name)->getValue();

    foreach ($field_values as &$field_value) {
      $field_value['value'] = _stanford_media_post_update_8202_change_tag($field_value['value']);
    }

    $entity->set($field_name, $field_values);
    $entity->save();
  }

  $sandbox['#finished'] = empty($sandbox['entities']) ? 1 : ($sandbox['count'] - count($sandbox['entities'])) / $sandbox['count'];
}

/**
 * Parse the drupal-entity tag, and change it into drupal-media tag.
 *
 * @param string $html
 *   Original html markup
 *
 * @return string
 *   New html markup with corrected tokens.
 */
function _stanford_media_post_update_8202_change_tag($html) {
  preg_match_all("/<drupal-entity.*?\/drupal-entity>/s", $html, $tokens);
  foreach ($tokens[0] as $token) {
    $token_dom = Html::load($token);
    $token_element = $token_dom->getElementsByTagName('drupal-entity')->item(0);

    $attributes = [
      'data-entity-uuid',
      'alt',
      'data-align',
    ];
    $new_token_attributes = new Attribute();
    $new_token_attributes->setAttribute('data-entity-type', 'media');

    foreach ($attributes as $attribute) {
      if ($value = $token_element->getAttribute($attribute)) {
        $new_token_attributes->setAttribute($attribute, $value);
      }
    }

    $display_settings = json_decode($token_element->getAttribute('data-entity-embed-display-settings'), TRUE);
    if (isset($display_settings['image_style'])) {
      $new_token_attributes->setAttribute('data-view-mode', _stanford_media_post_update_8202_image_display_mode($display_settings['image_style']));
    }
    if (isset($display_settings['description'])) {
      $new_token_attributes->setAttribute('data-display-description', $display_settings['description']);
    }

    if ($caption_data = json_decode(htmlspecialchars_decode($token_element->getAttribute('data-caption')), TRUE)) {
      $caption_text = strip_tags(check_markup($caption_data['value'], $caption_data['format']), '<a>');
      $new_token_attributes->setAttribute('data-caption', $caption_text ?: $caption_data['value']);
    }

    $new_token = "<drupal-media$new_token_attributes></drupal-media>";

    if (!empty($display_settings['linkit']['href'])) {
      $link_attributes = new Attribute($display_settings['linkit']);
      $new_token = "<a$link_attributes>$new_token</a>";
    }
    $html = str_replace($token, $new_token, $html);
  }

  return $html;
}

/**
 * Create a display mode for each image style.
 *
 * @param string $image_style
 *   Image style id.
 *
 * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
 *   The existing or new entity view display object.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _stanford_media_post_update_8202_image_display_mode($image_style) {
  $view_modes = &drupal_static(__FUNCTION__, []);
  if (empty($view_modes)) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    foreach (EntityViewDisplay::loadMultiple() as $display) {
      if ($display->getTargetEntityTypeId() != 'media' || $display->getTargetBundle() != 'image') {
        continue;
      }
      $field_display = $display->getComponent('field_media_image');
      if ($field_display && isset($field_display['settings']['image_style'])) {
        $view_modes[$field_display['settings']['image_style']] = $display->getMode();
      }
    }
  }

  if (!isset($view_modes[$image_style])) {
    $image_style_entity = ImageStyle::load($image_style);
    EntityViewMode::create([
      'id' => 'media.stanford_image_' . $image_style,
      'targetEntityType' => 'media',
      'label' => $image_style_entity ? $image_style_entity->label() : $image_style,
    ])->save();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $new_display */
    $new_display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image',
      'mode' => 'stanford_image_' . $image_style,
      'status' => TRUE,
    ]);
    $settings = [
      'label' => 'hidden',
      'type' => 'image',
      'settings' => ['image_style' => $image_style, 'image_link' => ''],
    ];
    $new_display->setComponent('field_media_image', $settings);
    $new_display->removeComponent('created');
    $new_display->removeComponent('thumbnail');
    $new_display->removeComponent('uid');
    $new_display->save();
    $view_modes[$image_style] = $new_display->getMode();
  }

  return $view_modes[$image_style];
}

/**
 * Get the list of entities with drupal-entity tokens.
 *
 * @return array
 *   List of entities needing fixed.
 */
function _stanford_media_post_update_8202_entity_list() {
  $list = [];
  module_load_install('stanford_media');
  foreach (_stanford_media_update_8005_get_filter_fields() as $field) {
    list($entity_type, $field_name) = explode(':', $field);

    try {
      $entity_ids = \Drupal::entityQuery($entity_type)
        ->condition($field_name, '<drupal-entity', 'CONTAINS')
        ->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('stanford_media')
        ->warning(t('Unable to query for %type entities with the field %field'), [
          '%type' => $entity_type,
          '%field' => $field_name,
        ]);
      continue;
    }

    foreach ($entity_ids as $entity_id) {
      $list[] = "$entity_type:$field_name:$entity_id";
    }
  }
  asort($list);
  return array_unique($list);
}

/**
 * Migrate video data to core supported fields.
 */
function stanford_media_post_update_8203(&$sandbox) {
  if (!isset($sandbox['count'])) {
    $sandbox['entities'] = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadByProperties(['bundle' => 'video']);

    $sandbox['count'] = count($sandbox['entities']);
  }

  $video_medias = array_splice($sandbox['entities'], 0, 25);

  /** @var \Drupal\media\MediaInterface $media */
  foreach ($video_medias as $media) {
    if (empty($media->get('field_media_oembed_video')->getString())) {
      $video_url = $media->get('field_media_video_embed_field')->getString();
      $media->set('field_media_oembed_video', $video_url);
      $media->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['entities']) ? 1 : ($sandbox['count'] - count($sandbox['entities'])) / $sandbox['count'];
}

/**
 * Delete unused configs now.
 */
function stanford_media_post_update_8204() {
  $browsers = EntityBrowser::loadMultiple([
    'file_browser',
    'image_browser',
    'media_browser',
    'video_browser',
  ]);
  foreach ($browsers as $browser) {
    $browser->delete();
  }

  EmbedButton::load('media_browser')->delete();
  View::load('media_entity_browser')->delete();
  \Drupal::messenger()
    ->addMessage(t('Review these modules to see if they still require being enabled: entity_browser, embed, & entity_embed'));
}
