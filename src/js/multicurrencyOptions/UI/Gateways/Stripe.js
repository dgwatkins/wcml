import React from "react";
import {InputRow, SelectRow} from "../FormElements";


const Stripe = ({gateway, settings, updateSettings, activeCurrencies, getName, currency}) => {
    return ! currency.isDefault && (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency}}
                       onChange={e => updateSettings({currency:e.target.value})}
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