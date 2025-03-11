jQuery(document).ready(function ($) {

  const { __, _x, _n, _nx } = wp.i18n;

  initTabs();
  initSelect2();
  initColorPicker();
  initRangeSlider();
  initMediaUploader();

  function initTabs() {
    $(".nav-links li a").on("click", function() {
      let selected = $(this).data('section');

      $(".nav-links li a").each(function() {
        let section = $(this).data('section');

        $(this).removeClass("active");

        if (selected == section) {
          $(this).addClass("active");
        }
      });

      $(".tabs .tab").each(function() {
        let tab = $(this);

        $(this).hide();

        if (tab.hasClass(selected)) {
          tab.show();
        }
      });
    });
  }

  function initSelect2() {
    $('#bypass_roles').select2({
      width: '100%',
      dropdownAutoWidth: true,
      placeholder: $('#bypass_roles').data('placeholder'),
      allowClear: true,
      closeOnSelect: true,
      dropdownCssClass: 'wp-core-ui',
      language: {
        noResults: function() {
          return __('No roles Found', 'speedy-search');
        }
      }
    });
  }

  function initColorPicker() {
    $('#color').wpColorPicker();
    $('#background_color').wpColorPicker();
  }

  function initRangeSlider() {
    var backgroundColorOpacity = $('#background_color_opacity').val();
    $('#range-value-display').text(backgroundColorOpacity);

    $('#background_color_opacity').on('input', function() {
      $('#range-value-display').text($(this).val());
    });
  }

  function initMediaUploader() {
    var mediaUploader;

    // If there's already an image URL, show the "Remove" button on page load
    if ($('#background_image').val() !== '') {
      $('.remove-background-image-button').show();
    }

    // Open the media uploader when the "Select Image" button is clicked
    $('.upload-background-image-button').click(function (e) {
      e.preventDefault();

      // If the media uploader already exists, open it
      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      // Create the media uploader
      mediaUploader = wp.media({
        title: __('Select a Background Image', 'speedy-search'),
        button: {
          text: __('Use This Image', 'speedy-search')
        },
        multiple: false
      });

      // When an image is selected, run this callback
      mediaUploader.on('select', function () {
        var attachment = mediaUploader.state().get('selection').first().toJSON();
        // Set the image URL in the hidden input field
        $('#background_image').val(attachment.url);

        // Display the image preview
        $('.background-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');

        // Show the "Remove" button
        $('.remove-background-image-button').show();
      });

      // Open the media uploader
      mediaUploader.open();
    });

    $('.remove-background-image-button').click(function () {
      // Clear the image URL input field
      $('#background_image').val('');

      // Remove the image preview
      $('.background-image-preview').html('');

      // Hide the "Remove" button
      $(this).hide();
    });
  }

});