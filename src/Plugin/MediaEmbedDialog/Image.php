<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogBase;
use Drupal\media\Plugin\media\Source\Image as ImageSource;
use Drupal\stanford_media\StanfordMedia;

/**
 * Changes embedded Image media form.
 *
 * @MediaEmbedDialog(
 *   id = "image"
 * )
 */
class Image extends MediaEmbedDialogBase {

  /**
   * List of tags that are allowed in the caption field.
   *
   * This list is pulled from Drupal core media ckeditor plugin.js.
   *
   * @var string
   */
  const CAPTION_ALLOWED_TAGS = '<a> <em> <strong> <cite> <code> <br>';

  /**
   * Alt text string that indicates the image is decorative.
   */
  const DECORATIVE = '[decorative]';

  /**
   * {@inheritdoc}
   */
  public function getDefaultInput(): array {
    return ['data-caption' => NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->getSource() instanceof ImageSource;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state): void {
    parent::alterDialogForm($form, $form_state);
    $user_input = $this->getUserInput($form_state);

    if (!isset($form['caption'])) {
      return;
    }

    $form['#process'][] = [StanfordMedia::class, 'imageWidgetProcess'];
    $form['#process'][] = [self::class, 'imageWidgetProcess'];
    // Allow a user to edit the caption text in the modal.
    $form['caption_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Caption Text'),
      '#description' => $this->t('Enter some contextual information about the image. Allowed tags are: @tags', ['@tags' => self::CAPTION_ALLOWED_TAGS]),
      '#default_value' => trim($user_input['data-caption']) ?? '',
      '#states' => [
        'visible' => [
          ':input[name="hasCaption"]' => ['checked' => TRUE],
        ],
      ],
    ];
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
    if (isset($element['alt']) && isset($element['decorative'])) {
      $element['decorative']['#default_value'] = $form['alt']['#default_value'] == self::DECORATIVE;
      $element['alt']['#default_value'] = $form['alt']['#default_value'] == self::DECORATIVE ? '' : $form['alt']['#default_value'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state): void {
    if ($form_state->getValue('decorative')) {
      // Set the alt text to some token that we can replace later.
      $form_state->setValue(['attributes', 'alt'], self::DECORATIVE);
    }
    if ($form_state->getValue('hasCaption')) {
      $text = $form_state->getValue('caption_text');
      $text = strip_tags($text, self::CAPTION_ALLOWED_TAGS);

      $values['attributes']['data-caption'] = $text;
      // We need to set a dummy attribute because the media's ckeditor plugin
      // javascript hashes all the attributes except `data-caption` and doing
      // this will allow the media preview to be re-generated when the caption
      // changes in the modal.
      $values['attributes']['data-caption-hash'] = substr(md5($text), 0, 5);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function embedAlter(array &$build, MediaInterface $entity): void {
    $source_field = self::getMediaSourceField($entity);
    // If the image is embed as decorative, set the alt text to an empty string.
    foreach (Element::children($build[$source_field]) as $delta) {
      /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $build[$source_field][$delta]['#item'];
      if ($item->get('alt')->getString() == self::DECORATIVE) {
        $item->set('alt', '');
      }
    }
    unset($build['#attributes']['data-caption-hash']);
  }

}
