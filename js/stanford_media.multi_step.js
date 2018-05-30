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

        $('.view-media-entity-browser').css('margin-bottom', $('.selection-action-wrapper').height());
      }

      $(window).resize(fixIframe);

      setTimeout(fixIframe, 500);

      $('.entity-browser-show-selection').once().click(function () {
        for (var i = 1; i <= 600; i++) {
          setTimeout(function () {
            $('.view-media-entity-browser').css('margin-bottom', $('.selection-action-wrapper').height());
          }, i);
        }
      })
    }
  };
})(jQuery, window, Drupal);
