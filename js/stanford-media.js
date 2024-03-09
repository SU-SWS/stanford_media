(function ($, Drupal, once) {
  'use strict';
  Drupal.behaviors.stanfordMedia = {
    attach: function (context, settings) {
      $(once('oembed-titles', '[data-oembed-resource]', context)).each(function () {
        const $oembedElement = $(this);
        fetch($oembedElement.attr('data-oembed-resource'))
          .then(response => response.json())
          .then(oembedData => {
            const elementTitle = oembedData.title || `${oembedData.provider_name} ${oembedData.type} ${oembedData.video_id}`.trim();
            $oembedElement.attr('title', elementTitle);
            $oembedElement.siblings('a.oembed-lazyload__button').attr('aria-label', `View ${elementTitle}`);
          });
      });
    },
  };

})(jQuery, Drupal, once);
