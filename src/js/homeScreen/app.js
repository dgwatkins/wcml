document.addEventListener('DOMContentLoaded', function() {
    const addFilter = wp.hooks.addFilter;

    addFilter(
        'woocommerce_admin_homepage_default_stats',
        'wcml/homescreen/remove_revenue_widgets',
        removeRevenueWidgets
    );
});

const removeRevenueWidgets = ( widgets ) => {
    return widgets.filter( function( value ) {
        return value.substr( 0, 8 ) !== 'revenue/';
    } );
}
