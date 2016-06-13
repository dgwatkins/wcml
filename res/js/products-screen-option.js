(function($) {
    'use strict';
    $(function(){
        $('body').on('click', 'button.notice-dismiss', function(e) {
            $.ajax({
                url:    ajaxurl,
                method: 'POST',
                data:   {
                    action:         'dismiss-notice',
                    nonce:          products_screen_option.nonce,
                    dismiss_notice: true
                },
            });
        });
    });
})(jQuery);