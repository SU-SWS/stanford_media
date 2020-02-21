<?php
/**
 * @file
 *
 * This filter is to remedy issues with libxml2 and core issues.
 * https://www.drupal.org/project/drupal/issues/1333730
 */

namespace Drupal\stanford_media\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * @Filter(
 *   id = "stanford_media_embed_markup",
 *   title = @Translation("Stanford Media Embed Filter"),
 *   description = @Translation("This helps with core markup issues. This filter has to run after the Embed Media filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class MediaEmbedMarkupFilter extends FilterBase {

  /**
   * {@inheritDoc}
   */
  public function process($text, $langcode) {
    $new_text = preg_replace('/><\/source>/m', "/>", $text);
    return new FilterProcessResult($new_text);
  }

}