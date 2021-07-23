const addTableColumn = reportTableData => {
	if ( reportTableData.endpoint !== 'orders' ) {
		return reportTableData;
	}

    const newHeaders = [
        ...reportTableData.headers,
        {
            label: wcmlReports.strings.languageLabel,
            key: 'language',
        },
    ];
    const newRows = reportTableData.rows.map( ( row, index ) => {
        const item     = reportTableData.items.data[ index ];

        const newRow = [
            ...row,
            {
                display: item.language,
                value: item.language,
            },
        ];

        return newRow;
    } );

    reportTableData.headers = newHeaders;
    reportTableData.rows = newRows;

    return reportTableData;
};

export default addTableColumn;
