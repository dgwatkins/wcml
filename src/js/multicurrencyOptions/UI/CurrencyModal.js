import React from "react";
import {useState} from "react";
import Modal from 'antd/lib/modal';
import Tooltip from 'antd/lib/tooltip';
import 'antd/lib/modal/style/index.css';
import 'antd/lib/tooltip/style/index.css';
import {useStore, getStoreProperty} from "../Store";
import {validateRate, getFormattedPricePreview} from '../Utils';

const CurrencyModal = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const onClose = () => setModalCurrency(null);
    const [isValidRate, setIsValidRate] = useState(true);
    const onChangeRate = (event) => {
        const rate = event.target.value;
        setModalCurrency({...currency, rate:rate});

        if (validateRate(rate)) {
            setIsValidRate(true);
        } else {
            setIsValidRate(false);
        }
    };

    const updateCurrencyProp = (prop) => (event) => {
        setModalCurrency({...currency, [prop]:event.target.value});
    };

    const onSave = () => {
        console.log(currency);
        onClose();
    };

    return <Modal
        title="The title"
        visible={true}
        onCancel={onClose}
        footer={<Footer onClose={onClose} onSave={onSave} disableSave={!isValidRate}></Footer>}

    >
        <div className="wcml-dialog wcml-dialog-container ui-dialog-content ui-widget-content" id={'wcml_currency_options_' + currency.code}>
            <div className="wcml_currency_options wcml-co-dialog">
                <form id={"wcml_currency_options_form_" + currency.code}>

                    {currency.isNew && <NewCurrencySelector updateCurrencyProp={updateCurrencyProp} />}

                    <div className="wpml-form-row wcml-co-exchange-rate">
                        <label htmlFor={"wcml_currency_options_rate_" + currency.code}>Exchange Rate</label>
                        <div className="wcml-co-set-rate">
                            1 BRL = <input name="currency_options[rate]" size="5" type="number"
                                           className="wcml-exchange-rate" min="0.01" step="0.01" value={currency.rate}
                                           data-message="Only numeric" id={"wcml_currency_options_rate_" + currency.code}
                                           onChange={onChangeRate}
                                    />
                            <span className="this-currency">{currency.code}</span>
                            {!isValidRate && <span className="wcml-error">Please enter a valid number</span>}
                            <small>
                                {currency.updated} <i></i>
                            </small>
                        </div>
                    </div>

                    <hr/>

                    <PreviewCurrency currency={currency} />

                    <SelectRow currency={currency} prop="position" updateCurrencyProp={updateCurrencyProp} label="Currency Position">
                        <option value="left">Left</option>
                        <option value="right">Right</option>
                        <option value="left_space">Left with space</option>
                        <option value="right_space">Right with space</option>
                    </SelectRow>

                    <SelectRow currency={currency} prop="thousand_sep" updateCurrencyProp={updateCurrencyProp} label="Thousand Separator">
                        <option value=".">.</option>
                        <option value=",">,</option>
                    </SelectRow>

                    <SelectRow currency={currency} prop="decimal_sep" updateCurrencyProp={updateCurrencyProp} label="Decimal Separator">
                        <option value=".">.</option>
                        <option value=",">,</option>
                    </SelectRow>

                    <InputRow currency={currency} prop='num_decimals' updateCurrencyProp={updateCurrencyProp} label='Number of Decimals' attrs={{min:'0', step:'1'}} />

                    <hr/>

                    <SelectRow currency={currency} prop="rounding" updateCurrencyProp={updateCurrencyProp} label="Rounding to the nearest integer" tooltip="To be defined!!!">
                        <option value="disabled">Disabled</option>
                        <option value="up">Up</option>
                        <option value="down">Down</option>
                        <option value="nearest">Nearest</option>
                    </SelectRow>

                    <SelectRow currency={currency} prop="rounding_increment" updateCurrencyProp={updateCurrencyProp} label="Increment for nearest integer" tooltip="To be defined!!!">
                        <option value="1">1</option>
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="1000">1000</option>
                    </SelectRow>

                    <InputRow currency={currency} prop='auto_subtract' updateCurrencyProp={updateCurrencyProp} label='Autosubtract amount' tooltip="To be defined!!!" />

                    <hr/>

                    <Gateways/>
                </form>
            </div>
        </div>
    </Modal>
};

export default CurrencyModal;

const Footer = ({onClose, onSave, disableSave}) => {
    return (
        <footer className="wpml-dialog-footer">
            <input type="button"
                   className="cancel wcml-dialog-close-button wpml-dialog-close-button alignleft"
                   onClick={onClose}
                   value="Cancel"
            />
            <input type="submit"
                   className="wcml-dialog-close-button wpml-dialog-close-button button-primary currency_options_save alignright"
                   onClick={onSave}
                   disabled={disableSave}
                   value="Save"
            />
        </footer>
    );
};

const PreviewCurrency = ({currency}) => {
    const allCurrencies = getStoreProperty('allCurrencies');
    const formatPrice = getFormattedPricePreview(allCurrencies);

    return (
        <div className="wpml-form-row wcml-co-preview">
            <label><strong>Currency Preview</strong></label>
            <p className="wcml-co-preview-value">
                <span className="woocommerce-Price-amount amount">
                    {formatPrice(currency)}
                </span>
            </p>
        </div>
    );
};

const getTooltip = (tooltip) => {
    return tooltip && <Tooltip title={tooltip}> <i className="wcml-tip otgs-ico-help"></i></Tooltip>;
};

const SelectRow = ({currency, prop, updateCurrencyProp, label, tooltip, children}) => {
    const id = "wcml_currency_options_" + prop + "_" + currency.code;

    return (
        <div className="wpml-form-row">
            <label htmlFor={id}>{label}{getTooltip(tooltip)}</label>
            <select id={id}
                    name={"currency_options[" + prop + "]"}
                    className={"currency_option_" + prop}
                    value={currency[prop]}
                    onChange={updateCurrencyProp(prop)}
            >
                {children}
            </select>
        </div>
    );
};

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

const NewCurrencySelector = ({updateCurrencyProp}) => {
    const newCurrencies = getStoreProperty('newCurrencies');

    return (
        <React.Fragment>
            <label htmlFor="wcml_currency_select_new">Select currency</label>
            <select name="wcml_currency_select_new"
                onChange={updateCurrencyProp('code')}
            >
                {
                    newCurrencies.map((currency) => <option key={currency.code} value={currency.code}>{currency.label}</option>)
                }
            </select>
        </React.Fragment>
    );
};

const Gateways = () => {
    const [checked, setChecked] = useState(false);

    return (
        <div>
            <label className="label-header"><strong>Payment Gateways</strong></label>

            <label className="wcml-gateways-switcher">
                <input name="currency_options[gateways_enabled]" type="checkbox"
                       className="wcml-gateways-enabled otgs-switcher-input" checked={checked} onChange={() => setChecked(!checked)}/>
                <span className="otgs-switcher wpml-theme" data-on="ON" data-off="OFF"></span>
                <a className="wpml-external-link"
                   href="https://wpml.org/?page_id=290080&utm_source=wcml-admin&utm_medium=plugin&utm_term=payment-gateways-settings&utm_content=documentation&utm_campaign=WCML#payment-gateways-settings"
                   target="_blank">Learn more</a>
            </label>
        </div>
    );
};
