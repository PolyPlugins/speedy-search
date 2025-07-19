jQuery(document).ready(function($) {

  let ajax_url = speedy_search_object.ajax_url;
  let nonce    = speedy_search_object.nonce;

  $('body').on('click', '.speedy-search .notice-dismiss', function() {
    $.post(ajax_url, {
      action: 'speedy_search_dismiss_notice_nonce',
      nonce: nonce
    });
  });

});
