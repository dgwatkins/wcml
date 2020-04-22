import React from "react";

const InputRow = ({currency, prop, updateCurrencyProp, label, tooltip, attrs={}}) => {
    const id = "wcml_currency_options_" + prop + "_" + currency.code;

    return (
        <div className="wpml-form-row">
            <label htmlFor={id}>{label}{getTooltip(tooltip)}</label>
            <input id={id}
                   name={"currency_options[" + prop + "]"}
                   className={"currency_option_" + prop}
                   {...attrs}
                   type="number"
                   data-message="Only numeric"
                   value={currency[prop]}
                   onChange={updateCurrencyProp(prop)}
            />
        </div>
    );
}