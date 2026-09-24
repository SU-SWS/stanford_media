<?php

namespace Drupal\stanford_media\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogManager;
use Drupal\stanford_media\StanfordMedia;
use Drupal\stanford_media\StanfordMediaInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Entity and form hook implementations for stanford_media.
 */
class StanfordMediaHooks {

  use StringTranslationTrait;

  /**
   * Hooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   * @param \Drupal\stanford_media\StanfordMediaInterface $stanfordMedia
   *   Stanford media service.
   * @param \Drupal\stanford_media\Plugin\MediaEmbedDialogManager $dialogManager
   *   Media embed dialog plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger channel factory service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    #[Autowire(service: 'stanford_media')]
    protected StanfordMediaInterface $stanfordMedia,
    #[Autowire(service: 'plugin.manager.media_embed_dialog_manager')]
    protected MediaEmbedDialogManager $dialogManager,
    protected MessengerInterface $messenger,
    protected ConfigFactoryInterface $configFactory,
    protected FileSystemInterface $fileSystem,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL): array {
    $operations = [];
    if ($entity->getEntityTypeId() != 'media') {
      return $operations;
    }

    $access = $entity->access('update', NULL, TRUE);
    $cacheability?->addCacheableDependency($access);
    if ($access->isAllowed()) {
      $operations['usage'] = [
        'title' => $this->t('Usage'),
        'weight' => 100,
        'url' => $entity->toUrl('usage'),
      ];
    }
    return $operations;
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('media_delete')]
  public function mediaDelete(MediaInterface $media): void {
    $this->stanfordMedia->deleteMediaFiles($media);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('media_presave')]
  public function mediaPresave(MediaInterface $entity): void {
    $source = $entity->getSource();
    // When the media entity has raw embed code, modify it using the validation
    // plugin before the entity is saved.
    if (
      $source->getPluginId() == 'embeddable' &&
      !$this->currentUser->hasPermission('bypass embed field validation')
    ) {
      /** @var \Drupal\stanford_media\Plugin\media\Source\Embeddable $source */
      if ($source->hasUnstructured($entity)) {
        $unstructured_field = $source->getConfiguration()['unstructured_field_name'] ?? '';
        $code = $source->getSourceFieldValue($entity);
        $code = $source->prepareEmbedCode($code);
        $entity->set($unstructured_field, $code);
      }
    }
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];
    if ($items->getFieldDefinition()->getType() == 'image') {
      $element['#process'][] = [StanfordMedia::class, 'imageWidgetProcess'];
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter().
   */
  #[Hook('media_view_alter')]
  public function mediaViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if (empty($build['#embed'])) {
      return;
    }

    foreach ($this->getDialogPlugins($entity) as $plugin) {
      $plugin->embedAlter($build, $entity);
    }
    $build['#attached']['library'][] = 'stanford_media/display';
  }

  /**
   * Get a list of all plugins applicable for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The media entity to check for plugins.
   *
   * @return \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface[]
   *   Array of applicable plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getDialogPlugins(EntityInterface $entity): array {
    $plugins = [];
    foreach (array_keys($this->dialogManager->getDefinitions()) as $plugin_id) {
      /** @var \Drupal\stanford_media\Plugin\MediaEmbedDialogInterface $plugin */
      $plugin = $this->dialogManager->createInstance($plugin_id, ['entity' => $entity]);

      if ($plugin->isApplicable()) {
        $plugins[$plugin_id] = $plugin;
      }
    }
    return $plugins;
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  #[Hook('media_prepare_form')]
  public function mediaPrepareForm(MediaInterface $media, $operation, FormStateInterface $form_state): void {
    // The entity usage service is fetched lazily so the hook class can still
    // be built when entity_usage is not enabled, such as in kernel tests.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $sources = $entity_usage->listSources($media);
    $count = 0;
    foreach ($sources as $source) {
      $count += count($source);
    }
    // Display a message to the user to alert them than editing will affect
    // multiple pieces of content.
    if ($count) {
      $plural_text = $this->formatPlural(
        $count,
        '@count piece of content',
        '@count pieces of content',
        [
          '@count' => $count,
        ]
      );

      $formatted_link = Link::fromTextAndUrl($plural_text, $media->toUrl('usage'));

      $message = $this->t(
        'Changing this media will affect @formatted_link.',
        [
          '@formatted_link' => $formatted_link->toString(),
        ]
      );
      $this->messenger->addWarning($message);
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_media_form_alter')]
  public function formMediaFormAlter(array &$form, FormStateInterface $form_state): void {
    // Adding styling for media form for the media prepare form items.
    $form['#attached']['library'][] = 'stanford_media/admin';
  }

  /**
   * Implements hook_entity_access().
   *
   * Restrict access to media entities that are used as field default values.
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Only lock down the media entities since they are the default field values
    // that we care about.
    if (
      $entity->getEntityTypeId() != 'media' ||
      !in_array($operation, ['update', 'delete'])
    ) {
      return AccessResult::neutral();
    }

    $configs = $this->configFactory->listAll('field.field.');
    foreach ($configs as $config_name) {
      $config = $this->configFactory->get($config_name);

      // Check for the fields we are interested in by checking their type and
      // handler settings.
      if (
        $config->get('field_type') == 'entity_reference' &&
        $config->get('settings.handler') == 'default:media' &&
        !empty($config->get('default_value'))
      ) {
        $default_value = $config->get('default_value');

        // The field default value matches the current media entity, so we want
        // to forbid editing/deleting if the user doesn't have the proper
        // permission.
        if (!empty($default_value[0]['target_uuid']) && $entity->uuid() == $default_value[0]['target_uuid']) {
          return AccessResult::forbiddenIf(!$account->hasPermission('edit field default media'), 'The entity is set as a default field value.');
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('file_insert')]
  public function fileInsert(FileInterface $file): void {
    $extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
    $file_path = $this->fileSystem->realpath($file->getFileUri());
    if (
      !in_array($extension, ['jpg', 'jpeg', 'png']) ||
      !$file_path ||
      !file_exists($file_path) ||
      !class_exists('Imagick')
    ) {
      return;
    }
    try {
      $image = new \Imagick($file_path);
      switch ($image->getImageOrientation()) {
        case \Imagick::ORIENTATION_BOTTOMRIGHT:
          // Rotate 180 degrees.
          $image->rotateimage("#000", 180);
          break;

        case \Imagick::ORIENTATION_RIGHTTOP:
          // Rotate 90 degrees CW.
          $image->rotateimage("#000", 90);
          break;

        case \Imagick::ORIENTATION_LEFTBOTTOM:
          // Rotate 90 degrees CCW.
          $image->rotateimage("#000", -90);
          break;
      }

      // Now that it's rotated, set the orientation, before stripping all other
      // tags.
      $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);

      // 1. Extract the ICC profile
      // 2. Strip EXIF data and image profile
      // 3. Add the ICC profile back
      $profiles = $image->getImageProfiles('icc');
      $image->stripImage();
      if (isset($profiles['icc'])) {
        $image->profileImage('icc', $profiles['icc']);
      }

      $image->writeImage($file_path);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('stanford_media')
        ->error('Unable to strip metadata & fix orientation from image: %path', ['%path' => $file_path]);
    }
  }

}
