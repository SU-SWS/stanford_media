<?php

namespace Drupal\stanford_media\Plugin\MediaEmbedDialog;

use Drupal\Core\Form\FormStateInterface;
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
   * {@inheritdoc}
   */
  public function getDefaultInput() {
    return ['data-caption' => NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    if ($this->entity instanceof MediaInterface) {
      return $this->entity->getSource() instanceof ImageSource;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDialogForm(array &$form, FormStateInterface $form_state) {
    parent::alterDialogForm($form, $form_state);
    $user_input = $this->getUserInput($form_state);

    if (!isset($form['caption'])) {
      return;
    }

    $form['#process'][] = [StanfordMedia::class, 'imageWidgetProcess'];
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
   * {@inheritdoc}
   */
  public function alterDialogValues(array &$values, array $form, FormStateInterface $form_state) {
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
  public function embedAlter(array &$build, MediaInterface $entity) {
    unset($build['#attributes']['data-caption-hash']);
  }

}
