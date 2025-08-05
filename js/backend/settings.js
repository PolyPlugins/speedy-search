jQuery(document).ready(function ($) {

  const { __, _x, _n, _nx } = wp.i18n;

  let ajax_url = snappy_search_object.ajax_url;
  let nonce    = snappy_search_object.nonce;

  initTabs();
  initSelect2();
  initColorPicker();
  initRangeSlider();
  initMediaUploader();
  initSubmit();

  function initTabs() {
    $("#toplevel_page_speedy-search .wp-submenu.wp-submenu-wrap").hide();
    
    const urlParams = new URLSearchParams(window.location.search);
    let currentTab = urlParams.get('tab') || 'general';

    // Check if the tab exists on the page before showing it
    if (!$(".tabs .tab." + currentTab).length) {
      currentTab = 'general'; // fallback if not found
    }

    // Set active tab on page load
    $(".tabs .tab").hide();
    $(".tabs .tab." + currentTab).show();

    $(".nav-links li a").each(function () {
      $(this).removeClass("active");
      if ($(this).data("section") === currentTab) {
        $(this).addClass("active");
      }
    });

    $(".nav-links li a").on("click", function() {
      let is_validated = initValidation();

      if (!is_validated) {
        return;
      }

      let selected = $(this).data('section');

      if (selected === 'reindex') {
        Swal.fire({
          title: "Reindex?",
          text: __("This will delete all indexes and rebuild them. Are you sure you want to do this?", 'speedy-search'),
          showDenyButton: false,
          showCancelButton: true,
          confirmButtonText: __("Reindex", 'speedy-search'),
          confirmButtonColor: "#46BEA4",
          showLoaderOnConfirm: true,
          preConfirm: () => {
            return $.ajax({
              url: ajax_url,
              type: 'POST',
              dataType: 'json',
              data: {
                action: 'speedy_search_reindex_all',
                nonce: nonce
              },
              success: function (response) {
                Swal.fire({
                  title: __("Success", 'speedy-search'),
                  text: __("Reindexing had begun!", 'speedy-search'),
                  icon: "success",
                  confirmButtonColor: "#46BEA4",
                });
              },
              error: function (xhr, status, error) {
                Swal.fire({
                  title: __("Error", 'speedy-search'),
                  text: __("Reindexing failed to start!", 'speedy-search'),
                  icon: "error",
                  confirmButtonColor: "#46BEA4",
                });

                console.error(__("Error: ", 'speedy-search'), error);
              }
            });
          }
        });

        return;
      }

      // Update the URL parameter "tab" without reloading the page
      const params = new URLSearchParams(window.location.search);
      params.set('tab', selected);
      const newUrl = window.location.pathname + '?' + params.toString();
      window.history.replaceState({}, '', newUrl);

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

  function initSubmit() {
    $(document).on("click", '#submit', function(e) {
      let is_validated = initValidation();

      if (!is_validated) {
        e.preventDefault();
      }
    })
  }

  function initValidation() {
    $tracking_delay = $("#tracking_delay").val();
    $typing_delay   = $("#typing_delay").val();

    if ($tracking_delay < $typing_delay) {
      Swal.fire({
        title: __("Error", 'speedy-search'),
        text: __("The tracking delay is set as ", 'speedy-search') + $tracking_delay + __("ms and must be larger than the typing delay of ", 'speedy-search') + $typing_delay + __("ms. Tracking relies on the search results being visible to the user, so if it runs too early before results are loaded it won't capture anything. This separation allows tracking to run in its own AJAX request after the main search, which helps keep the search fast and responsive.", 'speedy-search'),
        icon: "error",
        confirmButtonColor: "#46BEA4",
      });

      $("#tracking_delay").addClass("validation");
      $("#typing_delay").addClass("validation");

      return false;
    }

    $("#tracking_delay").removeClass("validation");
    $("#typing_delay").removeClass("validation");

    return true;
  }

});