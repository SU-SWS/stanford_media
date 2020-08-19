<?php

namespace Drupal\stanford_media\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\OEmbed;

/**
 * @MediaSource(
 *   id = "stanford_embed",
 *   label = @Translation("Stanford Embedded Media"),
 *   description = @Translation("Embeds a third-party resource."),
 *   providers = {"Deviantart.com", "Flickr"},
 *   allowed_field_types = {"string"},
 * )
 */
class Embeddable extends OEmbed {
  // No need for anything in here; the base plugin can take care of typical interactions
  // with external oEmbed services.
}
