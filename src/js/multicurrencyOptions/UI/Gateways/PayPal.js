import React from "react";
import {InputRow, SelectRow} from "../FormElements";
import {assocPath} from "ramda";


const PayPal = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {

    const onChangeCurrency = e => {
        const targetCurrency = e.target.value;
        const presetEmail = gateway.settings && gateway.settings[targetCurrency]
            ? gateway.settings[targetCurrency].value : '';

        updateSettings({currency:targetCurrency, value:presetEmail});
    };

    return ! currency.isDefault && (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency}}
                       onChange={onChangeCurrency}
                       label="Currency"
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <InputRow attrs={{name: getName('value'), value:settings.value, type:'text'}}
                       onChange={e => updateSettings({value:e.target.value})}
                       label="PayPal Email"
            />
        </React.Fragment>
    );
};

export default PayPal;