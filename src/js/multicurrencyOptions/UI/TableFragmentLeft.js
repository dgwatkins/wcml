import React from "react";
import {getSmallFormattedPrice, getCurrencyLabel} from '../Utils';
import {getStoreProperty} from "../Store";

const TableFragmentLeft = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');

    return <table className="widefat currency_table" id="currency-table">
                <thead>
                    <tr>
                        <th className="wcml-col-currency">Currency</th>
                        <th className="wcml-col-rate">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <Rows activeCurrencies={activeCurrencies}/>
                    <tr className="default_currency">
                        <td colSpan="3">Default currency
                            <i className="wcml-tip otgs-ico-help"
                               data-tip="strings.currencies_table.default_cur_tip"/>
                        </td>
                    </tr>
                </tbody>
            </table>
};

export default TableFragmentLeft;

const Rows = ({activeCurrencies}) => {
    const defaultCurrency = activeCurrencies.filter(currency => currency.isDefault)[0];

    return (
        <React.Fragment>
            {
                activeCurrencies.map((currency) => (
                        <Row key={currency.code}
                             currency={currency}
                             defaultCurrency={defaultCurrency}
                             formatPrice={getSmallFormattedPrice}
                             formatLabel={getCurrencyLabel}
                        />
                    )
                )
            }
        </React.Fragment>
    )
};

const Row = ({currency, defaultCurrency, formatPrice, formatLabel}) => {
    const rateDisplay = currency.isDefault
        ? <span className="truncate">default</span>
        : <span>1 {defaultCurrency.code} = <span className="rate">{currency.rate}</span> {currency.code}</span>;

    const id = 'currency_row_' + currency.code;

    return <tr id={id} className="wcml-row-currency">
                <td className="wcml-col-currency">
                    <span className="truncate">{formatLabel(currency.code)}</span>
                    <small>{formatPrice(currency)}</small>
                </td>
                <td className="wcml-col-rate">
                    {rateDisplay}
                </td>
            </tr>;
};