export default function () {
    var settings_screen = document.querySelector('.woocommerce_page_wc-settings');

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if ( mutation.type === 'childList' ) {
                var cost_fields = document.querySelectorAll('.wcml-shipping-cost-currency');
                if ( cost_fields.length > 0 ) {
                    var enable_manual_costs_selected = document.querySelector('.select.wcml-enable-shipping-custom-currency option[selected="selected"]');
                    cost_fields.forEach(function (field) {
                        field.closest('tr').hidden = enable_manual_costs_selected.value === 'auto';
                    });

                    var cost_selector = document.querySelector('.select.wcml-enable-shipping-custom-currency');
                    cost_selector.addEventListener( 'change', function( event ) {
                        cost_fields.forEach(function (field) {
                            field.closest('tr').hidden = cost_selector.value !== 'manual';
                        });
                    } );
                }
            }
        });
    });
    var config = { attributes: true, childList: true, characterData: true };
    observer.observe(settings_screen, config);
};