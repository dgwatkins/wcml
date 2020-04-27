import React from "react";
import {getStoreProperty, useStore} from "../Store";

const AddCurrency = () => {
    const [ , setModalCurrency] = useStore('modalCurrency');
    const newCurrencies = getStoreProperty('newCurrencies');
    const languages = getStoreProperty('languages');
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
            position: 'left',
            thousand_sep: '.',
            decimal_sep: ',',
            num_decimals: 2,
            rounding: 'disabled',
            rounding_increment: 1,
            auto_subtract: 0,
            isDefault: false,
            gatewaysEnabled: false,
            gatewaysSettings: {
                bacs: {currency:'', value:''},
                paypal: {currency:'', value:''},
                stripe: {currency:'', publishable_key:'', secret_key:''},
            },
        };

        setModalCurrency(newCurrency);
    };

    return hasNewCurrencies && (
        <button type="button"
                className="button-secondary wcml_add_currency alignright js-wcml-dialog-trigger"
                onClick={onClick}
        >
            <i className="otgs-ico-add otgs-ico-sm"></i> Add Currency
        </button>
    );
}

export default AddCurrency;