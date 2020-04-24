import React from "react";
import {InputRow, SelectRow} from "../FormElements";


const PayPal = ({gateway, settings, updateSettings, activeCurrencies, getName}) => {
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

            <InputRow attrs={{name: getName('value'), value:settings.value, type:'text'}}
                       onChange={updateSettings('value')}
                       label="PayPal Email"
            />
        </React.Fragment>
    );
};

export default PayPal;