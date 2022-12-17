<?php

namespace Drupal\stanford_media;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\media\MediaInterface;

/**
 * Class StanfordMedia.
 */
class StanfordMedia implements StanfordMediaInterface, TrustedCallbackInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file usage.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['imageWidgetProcess', 'imageWidgetValue'];
  }

  /**
   * Process callback for image widget fields.
   *
   * @param array $element
   *   Field element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $form
   *   Complete form.
   *
   * @return array
   *   Modified field element.
   */
  public static function imageWidgetProcess(array $element, FormStateInterface $form_state, array $form): array {
    if (!empty($element['alt']['#description'])) {
      $url = Url::fromUri('https://uit.stanford.edu/accessibility/concepts/images');
      $link = Link::fromTextAndUrl($url->toString(), $url)->toString();
      $element['alt']['#description'] = new TranslatableMarkup('Short description of the image used by screen readers and displayed when the image is not loaded. Leave blank if image is decorative. Learn more about alternative text: @link', ['@link' => $link]);
    }
    if ($element['#alt_field_required'] || !$element['alt']['#access']) {
      return $element;
    }

    $element['decorative'] = [
      '#type' => 'checkbox',
      '#title' => t('Decorative Image'),
      '#description' => t('Check this only if the image presents no additional information to the viewer.'),
      '#weight' => -99,
      '#default_value' => empty($element['alt']['#default_value']),
      '#attributes' => ['data-decorative' => TRUE],
    ];
    $element['alt']['#states']['visible'][':input[data-decorative]']['checked'] = FALSE;
    $element['alt']['#value_callback'] = [self::class, 'imageAltValue'];

    return $element;
  }

  /**
   * Image alt text field value callback.
   *
   * @param array $element
   *   Form element.
   * @param string|bool $input
   *   User entered value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return string
   *   Adjusted input string.
   */
  public static function imageAltValue(array &$element, $input, FormStateInterface $form_state): string {
    if ($input === FALSE) {
      return $element['#default_value'] ?: '';
    }
    $parents = array_slice($element['#parents'], 0, -1);
    $decorative_path = $parents;
    $decorative_path[] = 'decorative';
    if ($form_state->getValue($decorative_path)) {
      $form_state->setValue($element['#parents'], '');
      return '';
    }
    return $input ?: '';
  }

  /**
   * Constructs a new StanfordMedia.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileUsageInterface $file_usage, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteMediaFiles(MediaInterface $media): void {
    // Delete the file from the source field for files and image media types.
    if (in_array($media->getSource()->getPluginId(), ['file', 'image'])) {
      $media_type = $this->entityTypeManager->getStorage('media_type')
        ->load($media->bundle());
      $source_field = $media->getSource()
        ->getSourceFieldDefinition($media_type)
        ->getName();
      $this->deleteFileFromField($media, $source_field);
    }
    // Delete the thumbnail for videos and other media types.
    $this->deleteFileFromField($media, 'thumbnail');
  }

  /**
   * Grab the file entity from the media field and delete it.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   * @param string $field_name
   *   Field name for the file reference.
   */
  protected function deleteFileFromField(MediaInterface $media, string $field_name): void {
    if (!$media->hasField($field_name)) {
      return;
    }
    /** @var \Drupal\Core\Field\FieldItemInterface $field_value */
    $field_value = $media->get($field_name)->get(0);

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')
      ->load($field_value->get('target_id')->getString());

    $media_icon_path = $this->configFactory->getEditable('media.settings')
      ->get('icon_base_uri');

    // Remove the scheme of the icon path so that we don't delete private or
    // public directory icons.
    $media_icon_path = strpbrk($media_icon_path, '/');

    if (
      $file &&
      !$this->fileUsage->listUsage($file) &&
      !str_contains($file->getFileUri(), $media_icon_path)
    ) {
      $this->messenger()
        ->addStatus($this->t('Permanently deleted file from the server: @file', ['@file' => $file->getFilename()]));
      $file->delete();
    }
  }

}
