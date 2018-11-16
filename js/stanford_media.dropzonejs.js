(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.stanfordMediaDropzone = {
    attach: function attach(context, settings) {
      $('.similar-items-wrapper', context).once().each(function (i, wrapper) {
        var $entityForm = $(wrapper).siblings('[data-drupal-selector="edit-entity-form"]');
        $entityForm.hide();

        // Use change events here instead of in the form api because its much
        // harder to add listeners to the entire inline entity form.
        $(wrapper).find('input[type="radio"]').change(function () {
          if (this.value == '0') {
            $entityForm.show();
            resetFocalPoint($entityForm);
          }
          else {
            $entityForm.hide();
          }
        })
      });

      /**
       * Reset any focal point widget within the context.
       *
       * @param context
       */
      function resetFocalPoint(context) {
        $(".focal-point-indicator", context).each(function () {
          // Set some variables for the different pieces at play.
          var $indicator = $(this);
          var $img = $(this).siblings('img');
          var $previewLink = $(this).siblings('.focal-point-preview-link');
          var $field = $("." + $(this).attr('data-selector'));
          var fp = new Drupal.FocalPoint($indicator, $img, $field, $previewLink);
          fp.setIndicator();
        });
      }
    }
  };
})(jQuery, Drupal);
