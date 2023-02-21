<?php

namespace Drupal\stanford_media\Plugin;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media\MediaTypeInterface;
use Drupal\stanford_media\Annotation\BundleSuggestion;

/**
 * Class MediaEmbedManager.
 *
 * @package Drupal\stanford_media
 */
class BundleSuggestionManager extends DefaultPluginManager implements BundleSuggestionManagerInterface {

  /**
   * Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a BundleSuggestionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $field_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct(
      'Plugin/BundleSuggestion',
      $namespaces,
      $module_handler,
      BundleSuggestionInterface::class,
      BundleSuggestion::class
    );
    $this->alterInfo('bundle_suggestion_info');
    $this->setCacheBackend($cache_backend, 'bundle_suggestion_info_plugins');
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();

    $valid_definitions = [];
    // Find out which plugins should be used based on their field_types.
    foreach ($definitions as $plugin_id => $definition) {
      foreach ($definition['field_types'] as $field_type) {

        // A field for this plugin exists, so we can use this plugin.
        if ($this->fieldManager->getFieldMapByFieldType($field_type)) {
          $valid_definitions[$plugin_id] = $definition;
          continue 2;
        }
      }
    }

    return $valid_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestedBundle(string $input): ?MediaTypeInterface {
    if ($plugin = $this->getSuggestedBundlePlugin($input)) {
      return $plugin->getBundleFromString($input);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestedName(string $input): ?string {
    if ($plugin = $this->getSuggestedBundlePlugin($input)) {
      return $plugin->getName($input);
    }
    return NULL;
  }

  /**
   * Get the suggestion plugin that matches the input.
   *
   * @param string $input
   *   Url or string from the user.
   *
   * @return null|\Drupal\stanford_media\Plugin\BundleSuggestionInterface
   *   The matched bundle suggestion plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getSuggestedBundlePlugin(string $input): ?BundleSuggestionInterface {
    foreach ($this->getDefinitions() as $definition) {
      /** @var \Drupal\stanford_media\Plugin\BundleSuggestionInterface $plugin */
      $plugin = $this->createInstance($definition['id']);
      if ($plugin->getBundleFromString($input)) {
        return $plugin;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllExtensions(): array {
    $media_types = $this->getUploadBundles();
    return $this->getMultipleBundleExtensions(array_keys($media_types));
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadBundles(): array {
    $upload_bundles = [];
    $media_types = $this->getMediaBundles();

    foreach ($media_types as $media_type) {
      $source_field = $media_type->getSource()
        ->getConfiguration()['source_field'];
      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");

      if (!empty($field->getSetting('file_extensions'))) {
        $upload_bundles[$media_type->id()] = $media_type;
      }
    }

    return $upload_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleExtensions(MediaTypeInterface $media_type): array {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");
      $extensions = $field->getSetting('file_extensions') ?: '';
      return array_filter(explode(' ', $extensions));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleBundleExtensions(array $media_types): array {
    $media_types = $this->getMediaBundles($media_types);
    $extensions = [];
    foreach ($media_types as $media_type) {
      $extensions = array_merge($extensions, $this->getBundleExtensions($media_type));
    }
    return array_unique(array_filter($extensions));
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxFileSize(array $bundles = []): int {

    $media_types = $this->getUploadBundles();
    if ($bundles) {
      $media_types = $this->getMediaBundles($bundles);
    }

    $max_filesize = 0;
    foreach ($media_types as $media_type) {
      if ($max = $this->getMaxFileSizeBundle($media_type)) {
        if ($max > $max_filesize) {
          $max_filesize = $max;
        }
      }
    }
    $server_max = Bytes::toNumber(Environment::getUploadMaxSize());
    return !$max_filesize || $server_max < $max_filesize ? $server_max : $max_filesize;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxFileSizeBundle(MediaTypeInterface $media_type): int {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];

    if ($source_field) {
      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");
      return Bytes::toNumber($field->getSetting('max_filesize'));
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadPath(MediaTypeInterface $media_type): string {
    $source_field = $media_type->getSource()
      ->getConfiguration()['source_field'];
    $default_scheme = $this->configFactory->get('system.file')
      ->get('default_scheme');
    $path = "$default_scheme://";

    if ($source_field) {
      $field = $this->entityTypeManager->getStorage('field_config')
        ->load("media.{$media_type->id()}.$source_field");
      $scheme = $field->getSetting('uri_scheme') ?? $default_scheme;
      $path = $scheme . '://' . $field->getSetting('file_directory');
    }

    // Ensure the path has a trailing slash.
    if (strrpos($path, '/') !== strlen($path)) {
      $path .= '/';
    }
    return $path;
  }

  /**
   * Get all or some media bundles.
   *
   * @param string[] $bundles
   *   Optionally specify which media bundles to load.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   Keyed array of all media types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMediaBundles(array $bundles = []): array {
    /** @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($bundles ?: NULL);

    // Check the current user has access to create the various media types.
    return array_filter($media_types, function (MediaTypeInterface $media_type) {
      /** @var \Drupal\media\Entity\Media $empty_media */
      $empty_media = $this->entityTypeManager->getStorage('media')
        ->create(['bundle' => $media_type->id()]);
      return $empty_media->access('create');
    });
  }

}
