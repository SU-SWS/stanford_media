(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.stanfordMediaDropzone = {
    attach: function attach(context) {

      var events = [
        "drop",
        "dragstart",
        "dragend",
        "dragenter",
        "dragover",
        "dragleave",
        "addedfile",
        "addedfiles",
        "removedfile",
        "thumbnail",
        "error",
        "errormultiple",
        "processing",
        "processingmultiple",
        "uploadprogress",
        "totaluploadprogress",
        "sending",
        "sendingmultiple",
        "success",
        "successmultiple",
        "canceled",
        "canceledmultiple",
        "complete",
        "completemultiple",
        "reset",
        "maxfilesexceeded",
        "maxfilesreached",
        "queuecomplete"
      ];


      $.each(drupalSettings.dropzonejs.instances, function (i, dropzone) {
        dropzone.instance.on('addedfile', function (file) {
          // console.log('added', file);
          // console.log(dropzone);


          /*
          file._approveIcon = Dropzone.createElement("<div class='dropzonejs-approve-icon' title='Approve'></div>");
          file.previewElement.appendChild(file._approveIcon);
          file._approveIcon.addEventListener('click', function () {
            file._approveIcon.classList.add('approved');
            dropzone.instance.processFile(file);
          });

          */

          // dropzone.instance.cancelUpload(file);
          // dropzone.instance.options.error(file, 'Image already exists');
        });


        // dropzone.instance.on('sending', function (file, xhr, formData) {
        //   console.log('sending', file);
        //   console.log(xhr);
        //   console.log(formData);
        // });
        //
        // dropzone.instance.on('processing', function (file) {
        //   $('.dz-error-message, .dz-error-mark', file.previewElement).remove();
        // });
        // dropzone.instance.on('success', function (file, responseText, e) {
        //   console.log('sucssess', file.dataURL);
        //   console.log(responseText);
        //   console.log(e);
        // });
        //
        // dropzone.instance.on('complete', function (file) {
        //   console.log('complete', file);
        // });

      })
    }
  };
})(jQuery, Drupal, drupalSettings);
