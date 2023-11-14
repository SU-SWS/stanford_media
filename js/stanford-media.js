(function ($, Drupal, once) {
  'use strict';
  Drupal.behaviors.stanfordMedia = {
    attach: function (context, settings) {
      $(once('oembed-titles', '[data-oembed-resource]', context)).each(function () {
        const $oembedElement = $(this);
        fetch($oembedElement.attr('data-oembed-resource'))
          .then(response => response.json())
          .then(oembedData => $oembedElement.attr('title', oembedData.title || `${oembedData.provider_name} ${oembedData.type}`));
      });
    },
  };

})(jQuery, Drupal, once);
