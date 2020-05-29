import React from "react";
import {getStoreProperty} from "../../Store";
import strings from "../../Strings";
import {getCountryLabel} from '../../Utils';

const ColumnCountries = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');

    return <table className="widefat currency_country_table" id="currency-country-table">
                <thead>
                    <tr>
                        <th>{strings.labelCurrencyAvailableIn}</th>
                    </tr>
                </thead>
                <tbody>
                    {activeCurrencies.map(currency => <Row key={currency.code} currency={currency}/>)}
                </tbody>
            </table>
};

export default ColumnCountries;

const Row = ({currency}) => {
    return <tr id={'currency_row_countries_' + currency.code} className="wcml-row-currency-country">
        {currency.location_mode === '1' &&
            <td><span>{strings.labelAllCountries}</span></td>
        }

        {currency.location_mode === '2' &&
        <td>
            <span>{strings.labelAllCountriesExcept}: </span>
            <RenderCountries currency={currency}/>
        </td>
        }

        {currency.location_mode === '3' &&
            <td><RenderCountries currency={currency}/></td>
        }
    </tr>
};

const RenderCountries = ({currency}) => {
    const counties = currency.countries.map((country, i) => <span key={i}>{getCountryLabel(country)}</span>);
    const output = [];

    counties.forEach((country, i) => {
        // output the item
        output.push(country);
        // if list is more than 2 items, append a comma to all but the last item
        if (counties.length > 2 && i < counties.length - 1) output.push(',');
        // if list is more than 1 item, append a space to all but the last item
        if (counties.length > 1 && i < counties.length - 1) output.push(' ');
    });

    return output;
};
