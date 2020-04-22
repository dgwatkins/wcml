import React from "react";
import {InputRow, SelectRow} from "../FormElements";


const Bacs = ({gateway, settings, updateSettings, activeCurrencies}) => {
    return (
        <React.Fragment>
            <SelectRow attrs={{id:'bacs'}} onChange={e => console.log(e)} label="Currency">
                {
                    activeCurrencies.map((currency, key) => {
                        return <option key={key} value={currency.code}>{currency.code}</option>
                    })
                }
            </SelectRow>
        </React.Fragment>
    );
};

export default Bacs;