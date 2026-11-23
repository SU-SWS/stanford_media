<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\ImageDerivativeUtilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'media_image_formatter' formatter.
 */
#[FieldFormatter(
  id: "media_image_formatter",
  label: new TranslatableMarkup("Media Image Style"),
  description: new TranslatableMarkup("Apply an image style to image media items."),
  field_types: ["entity_reference"]
)]
class MediaImageFormatter extends MediaImageFormatterBase {

  /**
   * Image style utilities, only available on Drupal 11.4 and later.
   *
   * @var \Drupal\image\ImageDerivativeUtilities|null
   */
  protected ?ImageDerivativeUtilities $imageDerivativeUtilities = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    if ($container->has(ImageDerivativeUtilities::class)) {
      $instance->imageDerivativeUtilities = $container->get(ImageDerivativeUtilities::class);
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  protected function getStyleOptions(): array {
    return DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.4.0',
      fn() => $this->imageDerivativeUtilities->styleOptions(FALSE),
      fn() => image_style_options(FALSE),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender($element): array {
    $source_field = self::getSourceField($element['#media']);

    // If the field isn't an image, don't do anything.
    if (empty($element[$source_field]['#field_type']) || $element[$source_field]['#field_type'] != 'image') {
      return $element;
    }

    $element[$source_field]['#formatter'] = 'image';
    foreach (Element::children($element[$source_field]) as $delta) {
      $item = &$element[$source_field][$delta];
      $item['#theme'] = 'image_formatter';
      // If the field formatter is configured to remove the alt text.
      if (!empty($element['#stanford_media_remove_alt'])) {
        $item['#item']->set('alt', '');
      }
      $item['#image_style'] = $element['#stanford_media_image_style'];

      if (isset($element['#stanford_media_url'])) {
        $item['#url'] = $element['#stanford_media_url'];
        $item['#attributes']['title'] = $element['#stanford_media_url_title'];
      }
    }

    return $element;
  }

}
