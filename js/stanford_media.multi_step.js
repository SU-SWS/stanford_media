(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.stanfordMediaMultiStep = {
    attach: function attach() {

      // Resize the iframe to be as tall as the container.
      function fixIframe() {
        var $modal = $('#drupal-modal');
        var $iframe = $('#entity_browser_iframe_media_browser');

        if ($iframe.height() > $modal.height()) {
          $iframe.height($modal.height() - 5);
        }
      }

      $(window).resize(fixIframe);

      setTimeout(fixIframe, 500);
    }
  };
})(jQuery, window, Drupal);
