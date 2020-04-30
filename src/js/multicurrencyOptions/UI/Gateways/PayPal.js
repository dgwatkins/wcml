import React from "react";
import {InputRow, SelectRow} from "../FormElements";
import {getCurrencyIndex} from "../../Store"
import Tooltip from "antd/lib/tooltip";
import {sprintf} from "wpml-common-js-source/src/i18n";


const PayPal = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    const selectedCode = settings.currency || currency.code;
    const isSupportedCurrency = code => gateway.settings[code].isValid;

    const onChangeCurrency = e => {
        const targetCode = e.target.value;
        const targetIndex = getCurrencyIndex(activeCurrencies)(targetCode);
        const targetCurrency = activeCurrencies[targetIndex];
        const presetEmail = isSupportedCurrency(targetCode) && targetCurrency.gatewaysSettings && targetCurrency.gatewaysSettings[gateway.id]
            ? targetCurrency.gatewaysSettings[gateway.id].value : '';

        updateSettings({currency:targetCode, value:presetEmail});
    };

    const warning = ! isSupportedCurrency(selectedCode) && (
        <Tooltip title={sprintf(gateway.strings.tooltipNotSupported, selectedCode)}>
            <i className="wcml-tip otgs-ico-warning paypal-gateway-warning" />
        </Tooltip>
    );

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

            <InputRow attrs={{name: getName('value'), value:settings.value, type:'text', disabled:!isSupportedCurrency(selectedCode)}}
                       onChange={e => updateSettings({value:e.target.value})}
                       label={gateway.strings.labelPayPalEmail}
            />
        </React.Fragment>
    );
};

export default PayPal;