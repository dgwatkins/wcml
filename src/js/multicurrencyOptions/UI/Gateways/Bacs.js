import React from "react";
import {SelectRow} from "../../../sharedComponents/FormElements";

const Bacs = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    const valueOptions = {
        'all': gateway.strings.optionAll,
        'all_in': gateway.strings.optionAllIn,
        '0': ''
    };

    const settingsCurrency = currency.isDefault ? currency.code : settings.currency;

    return (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settingsCurrency, disabled:currency.isDefault}}
                       onChange={e => updateSettings({currency:e.target.value})}
                       label={gateway.strings.labelCurrency}
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <SelectRow attrs={{name: getName('value'), value:settings.value}}
                       onChange={e => updateSettings({value:e.target.value})}
                       label={gateway.strings.labelBankAccount}
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