import React from "react";
import {getStoreProperty, useStore} from "../Store";
import strings from "../Strings";

const AddCurrency = () => {
    const [ , setModalCurrency] = useStore('modalCurrency');
    const newCurrencies = getStoreProperty('newCurrencies');
    const languages = getStoreProperty('languages');
    const allCountries = getStoreProperty('allCountries');
    const hasNewCurrencies = !! newCurrencies.length;

    const onClick = () => {
        // All language are enabled by default
        const languageSettings = languages.reduce((carry, language) => {
            return {...carry, [language.code]:1};
        }, {});

        const newCurrency = {
            isNew: true,
            code: newCurrencies[0].code,
            languages: languageSettings,
            rate: 1,
            position: newCurrencies[0].position,
            thousand_sep: newCurrencies[0].thousand_sep,
            decimal_sep: newCurrencies[0].decimal_sep,
            num_decimals: newCurrencies[0].num_decimals,
            rounding: 'disabled',
            rounding_increment: 1,
            auto_subtract: 0,
            isDefault: false,
            gatewaysEnabled: false,
            gatewaysSettings: {
                'bacs': {currency:'', value:''},
                'paypal': {currency:'', value:''},
                'ppcp-gateway': {currency:'', merchant_email:'', merchant_id:'', client_id:'', client_secret: ''},
                'stripe': {currency:'', publishable_key:'', secret_key:''},
            },
            location_mode: 'all',
            countries: [],
        };

        setModalCurrency(newCurrency);
    };

    return hasNewCurrencies && (
        <button type="button"
                className="button-secondary wcml_add_currency alignright js-wcml-dialog-trigger"
                onClick={onClick}
        >
            <i className="otgs-ico-add otgs-ico-sm"/> {strings.labelAddCurrency}
        </button>
    );
}

export default AddCurrency;