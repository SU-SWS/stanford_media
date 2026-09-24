<?php

namespace Drupal\stanford_media\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\media\OEmbed\UrlResolverInterface;

/**
 * Theme hook implementations for stanford_media.
 */
class StanfordMediaThemeHooks {

  /**
   * Hooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $urlResolver
   *   OEmbed url resolver service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleList
   *   Module extension list service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected UrlResolverInterface $urlResolver,
    protected ModuleExtensionList $moduleList,
  ) {}

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if ($variables['entity_type'] != 'media') {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
    $field_items = $variables['element']['#items'];
    if (!$field_items instanceof FieldItemListInterface) {
      return;
    }
    /** @var \Drupal\media\MediaInterface $media */
    $media = $field_items->getEntity();

    // Make sure we're only working on the oembed source field.
    if (
      $media->getSource()->getPluginId() != 'oembed:video' ||
      $media->getSource()->getConfiguration()['source_field'] != $field_items->getName()
    ) {
      return;
    }

    foreach ($variables['items'] as $delta => &$item) {
      if (
        isset($item['content']['#attributes']['title']) ||
        isset($item['content']['#iframe']['#attributes']['title'])
      ) {
        // Title attribute is already provided.
        continue;
      }

      $url = $media->get($variables['field_name'])->get($delta)->getString();
      if ($url && UrlHelper::isValid($url)) {
        $oembed_url = $this->urlResolver->getResourceUrl($url);
        if (isset($item['content']['#iframe'])) {
          // Support oembed lazyload module.
          $item['content']['#iframe']['#attributes']['data-oembed-resource'] = $oembed_url;
        }

        $item['content']['#attributes']['data-oembed-resource'] = $oembed_url;

        $variables['#attached']['library'][] = 'stanford_media/display';
      }
    }
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    // Register the path to the template files.
    $theme_registry['dropzonejs']['path'] = $this->moduleList->getPath('stanford_media') . '/templates';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_dropzonejs')]
  public function preprocessDropzonejs(array &$variables): void {
    // Adds additional things to the template for the dropzone js widget.
    $variables['allowed_files'] = str_replace(' ', ', ', $variables['element']['#extensions']);
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(array &$variables): void {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $variables['media'];
    if ($media->getSource()->getPluginId() != 'file') {
      return;
    }
    $media_type = $this->entityTypeManager->getStorage('media_type')
      ->load($media->bundle());
    $source_field = $media->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();

    if (empty($variables['content'][$source_field][0]['#description'])) {
      $variables['content'][$source_field][0]['#description'] = $variables['name'];
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_filter_caption')]
  public function preprocessFilterCaption(array &$variables): void {
    if (!isset($variables['classes'])) {
      $variables['classes'] = '';
    }
    $variables['classes'] = trim($variables['classes'] . ' caption');
  }

}
