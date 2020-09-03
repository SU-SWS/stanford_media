<?php

namespace Drupal\stanford_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\stanford_media\Plugin\media\Source\Embeddable;
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

    if ($embed_type == "field_media_embeddable_oembed") {
      // Here, we override the Drupal\media\Plugin\Field\FieldFormatter\oEmbedFormatter
      // viewElements function, to make sure it works precisely the way we want.
      // To use the original version in core, just uncomment the following line.
      // return parent::viewElements($items, $langcode);.
      $element = [];
      $max_width = $this->getSetting('max_width');
      $max_height = $this->getSetting('max_height');

      foreach ($items as $delta => $item) {
        $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
        $value = $item->{$main_property};

        if (empty($value)) {
          continue;
        }

        try {
          $resource_url = $this->urlResolver->getResourceUrl($value, $max_width, $max_height);
          $resource = $this->resourceFetcher->fetchResource($resource_url);
        }
        catch (ResourceException $exception) {
          $this->logger->error("Could not retrieve the remote URL (@url).", ['@url' => $value]);
          continue;
        }

        if ($resource->getType() === Resource::TYPE_LINK) {
          $element[$delta] = [
            '#title' => $resource->getTitle(),
            '#type' => 'link',
            '#url' => Url::fromUri($value),
          ];
        }
        elseif ($resource->getType() === Resource::TYPE_PHOTO) {
          $element[$delta] = [
            '#theme' => 'image',
            '#uri' => $resource->getUrl()->toString(),
            '#width' => $max_width ?: $resource->getWidth(),
            '#height' => $max_height ?: $resource->getHeight(),
          ];
        }
        else {
          $url = Url::fromRoute('media.oembed_iframe', [], [
            'query' => [
              'url' => $value,
              'max_width' => $max_width,
              'max_height' => $max_height,
              'hash' => $this->iFrameUrlHelper->getHash($value, $max_width, $max_height),
            ],
          ]);

          $domain = $this->config->get('iframe_domain');
          if ($domain) {
            $url->setOption('base_url', $domain);
          }

          // Render videos and rich content in an iframe for security reasons.
          // @see: https://oembed.com/#section3
          // iFrame heights are a problem here. Some oEmbed providers don't give you one.
          // Some providers get it wrong, so we add a few pixels to be safe.
          // Here, we make some sane defaults.
          if (!empty($resource->getHeight())) {
            $iframe_height = $resource->getHeight() + 20;
          }
          else {
            $iframe_height = 300;
          }
          if ($iframe_height < $max_height) {
            $iframe_height = $max_height;
          }

          $element[$delta] = [
            '#type' => 'html_tag',
            '#tag' => 'iframe',
            '#attributes' => [
              'src' => $url->toString(),
              'frameborder' => 0,
              'scrolling' => FALSE,
              'allowtransparency' => TRUE,
              // We always want our iFrame to be full width.
              'width' => '100%',
              'height' => $iframe_height,
              'class' => ['media-oembed-content'],
            ],
            '#attached' => [
              'library' => [
                'media/oembed.formatter',
              ],
            ],
          ];

          // An empty title attribute will disable title inheritance, so only
          // add it if the resource has a title.
          $title = $resource->getTitle();
          if ($title) {
            $element[$delta]['#attributes']['title'] = $title;
          }

          CacheableMetadata::createFromObject($resource)
            ->addCacheTags($this->config->getCacheTags())
            ->applyTo($element[$delta]);
        }
      }
      return $element;
    }
    else {
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

}
