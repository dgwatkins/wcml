import addTableColumn from './Table';

document.addEventListener('DOMContentLoaded', function() {
    const addFilter = wp.hooks.addFilter;

    addFilter(
        'woocommerce_admin_report_table',
        'wcml/analytics/add-table-column',
        addTableColumn
    );
} );

