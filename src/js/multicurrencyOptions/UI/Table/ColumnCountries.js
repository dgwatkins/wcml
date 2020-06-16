import React from "react";
import {getStoreProperty} from "../../Store";
import strings from "../../Strings";
import {getCountryLabel} from '../../Utils';

const ColumnCountries = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');

    return <table className="widefat currency_country_table" id="currency-country-table">
                <thead>
                    <tr>
                        <th>{strings.labelAvailability}</th>
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
        {currency.location_mode === 'all' &&
            <td><span>{strings.labelAllCountries}</span></td>
        }

        {currency.location_mode === 'exclude' &&
        <td>
            <span className="all-countries-except">{strings.labelAllCountriesExcept}: </span>
            <RenderCountries currency={currency}/>
        </td>
        }

        {currency.location_mode === 'include' &&
            <td><RenderCountries currency={currency}/></td>
        }
    </tr>
};

const RenderCountries = ({currency}) => {

    return <span className="truncate">{
        currency.countries
            .map(country => getCountryLabel(country))
            .join(', ')
    }</span>;
};
