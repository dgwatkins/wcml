import React from "react";
import {InputRow, SelectRow} from "../FormElements";
import {getCurrencyIndex} from "../../Store";


const Stripe = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    const onChangeCurrency = e => {
        const targetCode = e.target.value;
        const targetIndex = getCurrencyIndex(activeCurrencies)(targetCode);
        const targetCurrency = activeCurrencies[targetIndex];
        const getSettingFromTarget = prop => {
            return targetCurrency.gatewaysSettings && targetCurrency.gatewaysSettings[gateway.id]
                ? targetCurrency.gatewaysSettings[gateway.id][prop] : '';
        }

        updateSettings({
            currency: targetCode,
            publishable_key: getSettingFromTarget('publishable_key'),
            secret_key: getSettingFromTarget('publishable_key')
        });
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

            <InputRow attrs={{name: getName('publishable_key'), value:settings.publishable_key, type:'password'}}
                      onChange={e => updateSettings({publishable_key:e.target.value})}
                      label="Live Publishable Key"
            />

            <InputRow attrs={{name: getName('secret_key'), value:settings.secret_key, type:'password'}}
                      onChange={e => updateSettings({secret_key:e.target.value})}
                      label="Live Secret Key"
            />
        </React.Fragment>
    );
};

export default Stripe;