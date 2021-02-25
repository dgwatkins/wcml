import { currencyFilters } from './Filters';
import addCurrencyFilter from './Filters';
import addTableColumn from './Table';
import persistCurrency from './Persist';
import updateReportCurrencies from './Format';

document.addEventListener('DOMContentLoaded', function() {
    const addFilter = wp.hooks.addFilter;

    currencyFilters.forEach( ( filter ) => {
        addFilter(
            filter,
            'wcml/analytics/add-currency-filter',
            addCurrencyFilter
        );
    } );

    addFilter(
        'woocommerce_admin_report_table',
        'wcml/analytics/add-table-column',
        addTableColumn
    );

    addFilter(
        'woocommerce_admin_persisted_queries',
        'wcml/analytics/persist-currency',
        persistCurrency
    );

    addFilter(
        'woocommerce_admin_report_currency',
        'wcml/analytics/update-report-currencies',
        updateReportCurrencies
    );
} );
