import React from "react";
import {getTooltip, InputRow, SelectRow} from "../../../sharedComponents/FormElements";
import {getCurrencyIndex} from "../../Store"
import {sprintf} from "wpml-common-js-source/src/i18n";


const PpcpGateway = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    const selectedCode = settings.currency || currency.code;
    const isSupportedCurrency = code => gateway.settings[code].isValid;

    const onChangeCurrency = e => {
        const targetCode = e.target.value;
        const targetIndex = getCurrencyIndex(activeCurrencies)(targetCode);
        const targetCurrency = activeCurrencies[targetIndex];
        const getSettingFromTarget = prop => {
            return isSupportedCurrency(targetCode) && targetCurrency.gatewaysSettings && targetCurrency.gatewaysSettings[gateway.id]
                ? targetCurrency.gatewaysSettings[gateway.id][prop] : '';
        }

        updateSettings({
            currency:targetCode,
            merchant_email:getSettingFromTarget('merchant_email'),
            merchant_id:getSettingFromTarget('merchant_id'),
            client_id:getSettingFromTarget('client_id'),
            client_secret:getSettingFromTarget('client_secret')
        });
    };

    const warning = ! isSupportedCurrency(selectedCode)
        && getTooltip(sprintf(gateway.strings.tooltipNotSupported, selectedCode), 'otgs-ico-warning paypal-gateway-warning')

    return ! currency.isDefault && (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency}}
                       onChange={onChangeCurrency}
                       label={gateway.strings.labelCurrency}
                       afterSelect={warning}
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <InputRow attrs={{name: getName('merchant_email'), value:settings.merchant_email, type:'text', disabled:!isSupportedCurrency(selectedCode)}}
                       onChange={e => updateSettings({merchant_email:e.target.value})}
                       label={gateway.strings.labelPayPalEmail}
            />
            <InputRow attrs={{name: getName('merchant_id'), value:settings.merchant_id, type:'text', disabled:!isSupportedCurrency(selectedCode)}}
                       onChange={e => updateSettings({merchant_id:e.target.value})}
                       label={gateway.strings.labelMerchantId}
            />
            <InputRow attrs={{name: getName('client_id'), value:settings.client_id, type:'text', disabled:!isSupportedCurrency(selectedCode)}}
                       onChange={e => updateSettings({client_id:e.target.value})}
                       label={gateway.strings.labelClientId}
            />
            <InputRow attrs={{name: getName('client_secret'), value:settings.client_secret, type:'text', disabled:!isSupportedCurrency(selectedCode)}}
                       onChange={e => updateSettings({client_secret:e.target.value})}
                       label={gateway.strings.labelSecretKey}
            />
        </React.Fragment>
    );
};

export default PpcpGateway;