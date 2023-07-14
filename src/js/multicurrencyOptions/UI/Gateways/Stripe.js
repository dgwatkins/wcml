import React from "react";
import {InputRow, SelectRow} from "../../../sharedComponents/FormElements";
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
            secret_key: getSettingFromTarget('secret_key'),
            test_publishable_key: getSettingFromTarget('test_publishable_key'),
            test_secret_key: getSettingFromTarget('test_secret_key')
        });
    };

    return ! currency.isDefault && (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency}}
                       onChange={onChangeCurrency}
                       label={gateway.strings.labelCurrency}
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <InputRow attrs={{name: getName('publishable_key'), value:settings.publishable_key, type:'text'}}
                      onChange={e => updateSettings({publishable_key:e.target.value})}
                      label={gateway.strings.labelLivePublishableKey}
            />

            <InputRow attrs={{name: getName('secret_key'), value:settings.secret_key, type:'text'}}
                      onChange={e => updateSettings({secret_key:e.target.value})}
                      label={gateway.strings.labelLiveSecretKey}
            />

            <InputRow attrs={{name: getName('test_publishable_key'), value:settings.test_publishable_key, type:'text'}}
                      onChange={e => updateSettings({test_publishable_key:e.target.value})}
                      label={gateway.strings.labelTestPublishableKey}
            />

            <InputRow attrs={{name: getName('test_secret_key'), value:settings.test_secret_key, type:'text'}}
                      onChange={e => updateSettings({test_secret_key:e.target.value})}
                      label={gateway.strings.labelTestSecretKey}
            />
        </React.Fragment>
    );
};

export default Stripe;