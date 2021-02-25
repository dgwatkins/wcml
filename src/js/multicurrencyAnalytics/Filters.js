const currencyFilters = new Set( [
    'woocommerce_admin_dashboard_filters',
    'woocommerce_admin_products_report_filters',
    'woocommerce_admin_revenue_report_filters',
    'woocommerce_admin_orders_report_filters',
    'woocommerce_admin_variations_report_filters',
    'woocommerce_admin_categories_report_filters',
    'woocommerce_admin_coupons_report_filters',
    'woocommerce_admin_taxes_report_filters'
] );

const addCurrencyFilter = ( filters ) => {
    return [
        ...filters,
        {
            label: wcmlAnalytics.strings.currencyLabel,
            staticParams: [],
            param: 'currency',
            showFilters: () => true,
            defaultValue: wcSettings.currency.code || 'USD',
            filters: [ ...( wcmlAnalytics.filterItems || [] ) ],
            settings: [],
        },
    ];
};

export default addCurrencyFilter;
export { currencyFilters };
