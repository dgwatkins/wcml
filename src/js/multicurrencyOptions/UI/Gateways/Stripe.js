import React from "react";
import {InputRow, SelectRow} from "../FormElements";


const Stripe = ({gateway, settings, updateSettings, activeCurrencies, getName}) => {
    return (
        <React.Fragment>
            <SelectRow attrs={{name: getName('currency'), value:settings.currency}}
                       onChange={updateSettings('currency')}
                       label="Currency"
            >
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>;
                    })
                }
            </SelectRow>

            <InputRow attrs={{name: getName('publishable_key'), value:settings.publishable_key, type:'password'}}
                      onChange={updateSettings('publishable_key')}
                      label="Live Publishable Key"
            />

            <InputRow attrs={{name: getName('secret_key'), value:settings.secret_key, type:'password'}}
                      onChange={updateSettings('secret_key')}
                      label="Live Secret Key"
            />
        </React.Fragment>
    );
};

export default Stripe;