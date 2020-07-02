import React from "react";
import {getStoreProperty} from "../../Store";
import strings from "../../Strings";
import {getCountryLabel} from '../../Utils';

const ColumnCountries = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');

    return <div className="currency_wrap">
                <div className="currency_inner">
                    <table className="widefat currency_country_table" id="currency-country-table">
                        <thead>
                            <tr>
                                <th>{strings.labelAvailability}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activeCurrencies.map(currency => <Row key={currency.code} currency={currency}/>)}
                        </tbody>
                    </table>
                </div>
            </div>
};

export default ColumnCountries;

const Row = ({currency}) => {
    return <tr id={'currency_row_countries_' + currency.code} className="wcml-row-currency-country">
        {currency.location_mode === 'all' &&
            <td><span>{strings.labelAllCountries}</span></td>
        }

        {currency.location_mode === 'exclude' &&
        <td>
            <RenderCountries currency={currency} label={strings.labelAllCountriesExcept}/>
        </td>
        }

        {currency.location_mode === 'include' &&
            <td><RenderCountries currency={currency}/></td>
        }
    </tr>
};

const RenderCountries = ({currency, label}) => {

    return <span className="truncate">{label}{
        currency.countries
            .map(country => getCountryLabel(country))
            .join(', ')
    }</span>;
};
