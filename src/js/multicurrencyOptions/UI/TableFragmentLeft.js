import React from "react";

const TableFragmentLeft = ({currencies}) => {
    const defaultCurrency = currencies.filter(currency => currency.default);

    return <table className="widefat currency_table" id="currency-table">
                <thead>
                <tr>
                    <th className="wcml-col-currency">Currency</th>
                    <th className="wcml-col-rate">Rate</th>
                </tr>
                </thead>
                <tbody>

                <Rows currencies={currencies}/>

                <tr className="default_currency">
                    <td colSpan="3">Default currency
                        <i className="wcml-tip otgs-ico-help"
                           data-tip="strings.currencies_table.default_cur_tip"></i>
                    </td>
                </tr>

                </tbody>
            </table>
};

export default TableFragmentLeft;

const Rows = ({currencies}) => {
    const defaultCurrency = currencies.filter( currency => currency.default )[0];

    return (
        <React.Fragment>
            {
                currencies.map((currency) => (
                        <Row currency={currency} defaultCurrency={defaultCurrency} />
                    )
                )
            }
        </React.Fragment>
    )
};

const Row = ({currency, defaultCurrency}) => {
    const rateDisplay = currency.default
        ? <span className="truncate">default</span>
        : <span>1 {defaultCurrency.code} = <span className="rate">{currency.rate}</span> {currency.code}</span>;

    const id = 'currency_row_' + currency.code;

    return <tr id={id} className="wcml-row-currency">
        <td className="wcml-col-currency">
            <span className="truncate">{currency.label}</span>
            <small>$99.99</small>
        </td>
        <td className="wcml-col-rate">
            {rateDisplay}
        </td>
    </tr>;
};