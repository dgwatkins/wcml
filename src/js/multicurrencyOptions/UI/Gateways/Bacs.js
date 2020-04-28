import React from "react";
import {SelectRow} from "../FormElements";


const Bacs = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    const valueOptions = {
        'all': 'All accounts',
        'all_in': 'All in selected currency',
        '0': ''
    };

    return (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency, disabled:currency.isDefault}}
                       onChange={updateSettings('currency')}
                       label="Currency"
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <SelectRow attrs={{name: getName('value'), value:settings.value}}
                       onChange={updateSettings('value')}
                       label="Bank Account"
            >
                {
                    Object.keys(valueOptions).map((value) => {
                        return <option key={value} value={value}>{valueOptions[value]}</option>;
                    })
                }
            </SelectRow>
        </React.Fragment>
    );
};

export default Bacs;