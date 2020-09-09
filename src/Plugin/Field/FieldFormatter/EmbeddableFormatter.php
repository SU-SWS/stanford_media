<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Messenger\MessengerInterface;
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
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);

    $this->oEmbedField = $config_factory->get('media.type.embeddable')->get('source_configuration.oembed_field_name');
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media' || !$field_definition->getTargetBundle()) {
      return FALSE;
    }

    $media_type = MediaType::load($field_definition->getTargetBundle());
    return $media_type && $media_type->getSource() instanceof Embeddable;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $embed_type = $items->getName();
    return ($embed_type == $this->oEmbedField) ?
      $this->viewOEmbedElements($items, $langcode) : $this->viewUnstructuredElements($items, $langcode);
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
   *   A renderable array.
   */
  protected function viewOEmbedElements(FieldItemListInterface $items, $langcode) {
    $items = parent::viewElements($items, $langcode);
    foreach ($items as &$render_array) {

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
    return $items;
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
   *   A renderable array.
   */
  protected function viewUnstructuredElements(FieldItemListInterface $items, $langcode) {
    // Here, we will handle Embeddables that are unstructured, and just inject the
    // markup unchanged.
    $elements = [];
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $delta => $item) {
      $embed_markup = $item->getValue()['value'];
      if (!empty($embed_markup)) {
        $elements[$delta] = [
          '#markup' => $item->getValue()['value'],
          '#allowed_tags' => [
            'iframe',
            'video',
            'audio',
            'source',
            'embed',
            'script',
          ],
          '#prefix' => '<div class="embeddable-content">',
          '#suffix' => '</div>',
        ];
      }
    }
    return $elements;
  }

}
