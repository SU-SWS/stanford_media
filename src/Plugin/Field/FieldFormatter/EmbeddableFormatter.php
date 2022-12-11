<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter;

/**
 * Field formatter for embeddables.
 *
 * @FieldFormatter (
 *   id = "embeddable_formatter",
 *   label = @Translation("Embeddable field formatter"),
 *   description = @Translation("Field formatter for Embeddable media."),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class EmbeddableFormatter extends OEmbedFormatter {

  /**
   * The name of the oEmbed field.
   *
   * @var string
   */
  protected $oEmbedField;

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['allowed_tags'] = 'iframe script div a';
    return $settings;
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media' || !$field_definition->getTargetBundle()) {
      return FALSE;
    }

    $media_type = self::getMediaType($field_definition->getTargetBundle());
    return $media_type && $media_type->getSource() instanceof Embeddable;
  }

  /**
   * Load the media type from the media type id.
   *
   * @param string $media_type_id
   *   Media type machine name.
   *
   * @return \Drupal\media\MediaTypeInterface|null
   *   Media type entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getMediaType($media_type_id): ?MediaTypeInterface {
    return \Drupal::entityTypeManager()
      ->getStorage('media_type')
      ->load($media_type_id);
  }

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);

    $media_type = self::getMediaType($field_definition->getTargetBundle());
    $this->oEmbedField = $media_type->getSource()
      ->getConfiguration()['source_field'];
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML Tags'),
      '#default_value' => $this->getSetting('allowed_tags'),
      '#description' => $this->t('HTML Tags that will be allowed for the unstructured embeddable formatter'),
      '#element_validate' => [[$this, 'validateAllowedTags']],
      '#maxlength' => NULL,
    ];
    return $element;
  }

  /**
   * Validate the allowed tags value by removing non alpha characters.
   *
   * @param array $element
   *   Form Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   * @param array $form
   *   Complete form.
   */
  public function validateAllowedTags(array $element, FormStateInterface $form_state, array $form): void {
    $tags = $form_state->getValue($element['#parents']);
    $adjusted_tags = preg_replace('/  +/', ' ', preg_replace('/[^a-z ]/', '', strtolower($tags)));
    $form_state->setValue($element['#parents'], trim($adjusted_tags));
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $embed_type = $items->getName();
    return $embed_type == $this->oEmbedField ? $this->viewOEmbedElements($items, $langcode) : $this->viewUnstructuredElements($items, $langcode);
  }

  /**
   * Format oEmbeds.
   *
   * This overrides parent::viewElements function and corrects some problems.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The content of the field.
   * @param string $langcode
   *   The language.
   *
   * @return array
   *   A render array.
   */
  protected function viewOEmbedElements(FieldItemListInterface $items, string $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$render_array) {

      // We only care about modifying iframes.
      if ($render_array['#type'] == 'html_tag' && $render_array['#tag'] == 'iframe') {

        // iFrame heights are a problem here. Some oEmbed providers don't give you one.
        // Some providers get it wrong, so we add a few pixels to be safe.
        // Here, we make some sane defaults.
        $max_height = $this->getSetting('max_height');
        $iframe_height = $render_array['#attributes']['height'];
        $iframe_height = (!empty($iframe_height)) ? $iframe_height + 20 : 300;
        $iframe_height = ($iframe_height < $max_height) ? $max_height : $iframe_height;

        // Correct the render array to make it a117 compliant and appropriate to our purposes.
        unset($render_array['#attributes']['frameborder']);
        unset($render_array['#attributes']['scrolling']);
        unset($render_array['#attributes']['allowtransparency']);
        unset($render_array['#attributes']['width']);
        $render_array['#attributes']['style'] = 'height: ' . $iframe_height . 'px;';
      }

    }
    return $elements;
  }

  /**
   * Format unstructured embeds.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The content of the field.
   * @param string $langcode
   *   The language.
   *
   * @return array
   *   A render array.
   */
  protected function viewUnstructuredElements(FieldItemListInterface $items, string $langcode): array {
    // Here, we will handle Embeddables that are unstructured, and just inject the
    // markup unchanged.
    $elements = [];
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $delta => $item) {
      $embed_markup = $item->getValue()['value'];
      if (!empty($embed_markup)) {
        $elements[$delta] = [
          '#markup' => $item->getValue()['value'],
          '#allowed_tags' => explode(' ', $this->getSetting('allowed_tags')),
          '#prefix' => '<div class="embeddable-content">',
          '#suffix' => '</div>',
        ];
      }
    }
    return $elements;
  }

}
