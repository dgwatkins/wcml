const updateReportCurrencies = ( config, { currency } ) => {
    if ( currency && wcmlAnalytics.currencyConfigs[ currency ] ) {
        return wcmlAnalytics.currencyConfigs[ currency ];
    }
    return config;
};

export default updateReportCurrencies;
