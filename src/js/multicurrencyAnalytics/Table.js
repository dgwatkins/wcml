const addTableColumn = reportTableData => {
	const includedReports = [
		'revenue',
		'products',
		'orders',
		'categories',
		'coupons',
		'taxes',
	];
	if ( ! includedReports.includes( reportTableData.endpoint ) ) {
		return reportTableData;
	}

    const newHeaders = [
        ...reportTableData.headers,
        {
            label: wcmlAnalytics.strings.currencyLabel,
            key: 'currency',
        },
    ];
    const newRows = reportTableData.rows.map( ( row, index ) => {
        const item     = reportTableData.items.data[ index ];
        const currency = reportTableData.endpoint === 'revenue'
            ? item.subtotals.currency
            : item.currency;

        const newRow = [
            ...row,
            {
                display: currency,
                value: currency,
            },
        ];

        return newRow;
    } );

    reportTableData.headers = newHeaders;
    reportTableData.rows = newRows;

    return reportTableData;
};

export default addTableColumn;
