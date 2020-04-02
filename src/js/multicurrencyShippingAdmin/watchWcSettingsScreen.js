export default function () {
    // css selectors
    const shipping_costs_field_selector          = '.wcml-shipping-cost-currency';
    const enable_manual_costs_selector           = '.select.wcml-enable-shipping-custom-currency';
    const enable_manual_costs_selected_selector  = enable_manual_costs_selector + ' option[selected="selected"]';

    const hide_costs_fields_when_not_enabled = ( cost_fields_element ) => {
        const enable_manual_costs_selected_element = document.querySelector( enable_manual_costs_selected_selector );
        cost_fields_element.forEach( (field) => {
            field.closest('tr').hidden = enable_manual_costs_selected_element.value === 'auto';
        });
    }

    const toggle_fields_display = ( cost_fields_element ) => {
        const cost_selector_element = document.querySelector( enable_manual_costs_selector );
        cost_selector_element.addEventListener( 'change', function( event ) {
            cost_fields_element.forEach( (field) => {
                field.closest('tr').hidden = cost_selector_element.value !== 'manual';
            });
        } );
    }

    const observer = new MutationObserver( ( mutations ) => {
        mutations.forEach( (mutation) => {
            if ( mutation.type === 'childList' ) {
                const cost_fields_element = document.querySelectorAll( shipping_costs_field_selector );
                if ( cost_fields_element.length > 0 ) {
                    hide_costs_fields_when_not_enabled( cost_fields_element );
                    toggle_fields_display( cost_fields_element );
                }
            }
        });
    });

    const settings_screen_element = document.querySelector('.woocommerce_page_wc-settings');
    const observer_config         = { attributes: true, childList: true, characterData: true };
    observer.observe( settings_screen_element, observer_config );
};