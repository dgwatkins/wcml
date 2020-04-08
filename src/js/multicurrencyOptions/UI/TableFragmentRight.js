import React from "react";

const TableFragmentRight = ({currencies}) => {
    return <table className="widefat currency_settings_table" id="currency-settings-table">
                <thead>
                    <tr>
                        <th colSpan="2">Settings</th>
                    </tr>
                </thead>
                <tbody>

                    {currencies.map(currency => <Row key={currency.code} currency={currency} />)}

                    <tr className="default_currency">
                        <td colSpan="2"></td>
                    </tr>
                </tbody>
            </table>
};

export default TableFragmentRight;

const Row = ({currency}) => {
    const titleDelete = 'Delete';
    const titleEdit = 'Edit';
    const dataKey = 'wcml_currency_options_' + currency.code;

    const deleteCell = ! currency.default
        && (
            <td className="wcml-col-delete">
                <a title={titleDelete} className="delete_currency"
                   data-currency_name={currency.label}
                   data-currency_symbol={currency.symbol}
                   data-currency={currency.code} href="#">
                    <i className="otgs-ico-delete" title={titleDelete} />
                </a>
            </td>
        );

    return <tr id={'wcml-row-currency-actions-' + currency.code } className="wcml-row-currencies-actions">
                <td className="wcml-col-edit">
                    <a href="#" title={titleEdit}
                       className="edit_currency js-wcml-dialog-trigger"
                       data-currency={currency.code} data-content={dataKey}
                       data-dialog={dataKey}
                       data-height="530" data-width="480">
                        <i className="otgs-ico-edit" title={titleEdit} />
                    </a>
                </td>
                {deleteCell}
            </tr>
};
