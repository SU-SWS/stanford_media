<?php

namespace Drupal\stanford_media\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * MediaEmbedMarkupFilter for fixing core issues.
 *
 * @deprecated in 10.1.5 and removed in 11.0.0.
 *   Fixed in https://www.drupal.org/project/drupal/issues/1333730.
 *   No longer necessary since Drupal 10.2.0 WYSIWYG supports HTML5.
 *
 * @Filter(
 *   id = "stanford_media_embed_markup",
 *   title = @Translation("Stanford Media Embed Filter"),
 *   description = @Translation("DEPRECATED: No longer necessary in Drupal 10.2.0+. This helps with core markup issues. This filter has to run after the Embed Media filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class MediaEmbedMarkupFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // This filter is to remedy issues with libxml2 and core issues.
    // https://www.drupal.org/project/drupal/issues/1333730.
    $new_text = preg_replace('/><\/source>/m', "/>", $text);
    return new FilterProcessResult($new_text);
  }

}
