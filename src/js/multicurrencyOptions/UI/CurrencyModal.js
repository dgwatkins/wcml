import React from "react";
import {useState} from "react";
import Modal from 'antd/lib/modal';
import 'antd/lib/modal/style/index.css';
import 'antd/lib/tooltip/style/index.css';
import {useStore, getStoreProperty, getStoreAction} from "../Store";
import {validateRate, getFormattedPricePreview} from '../Utils';
import Gateways from "./Gateways/Gateways";
import {SelectRow, InputRow} from "./FormElements";

const CurrencyModal = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const saveModalCurrency = getStoreAction('saveModalCurrency');
    const [isValidRate, setIsValidRate] = useState(true);
    const onClose = () => setModalCurrency(null);

    const updateCurrencyProp = (prop) => (e) => {
        setModalCurrency({...currency, [prop]:e.target.value});
    };

    const onChangeRate = (e) => {
        updateCurrencyProp('rate')(e);

        if (validateRate(e.target.value)) {
            setIsValidRate(true);
        } else {
            setIsValidRate(false);
        }
    };

    const onSave = () => {
        console.log(currency);
        saveModalCurrency();
        onClose();
    };

    return <Modal
        title="The title"
        visible={true}
        onCancel={onClose}
        footer={<Footer onClose={onClose} onSave={onSave} disableSave={!isValidRate} />}

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
                            <small>{currency.updated}</small>
                        </div>
                    </div>

                    <hr/>

                    <PreviewCurrency currency={currency} />

                    <SelectRow attrs={getRowAttrs(currency, 'position')}
                               onChange={updateCurrencyProp('position')}
                               label="Currency Position"
                    >
                        <option value="left">Left</option>
                        <option value="right">Right</option>
                        <option value="left_space">Left with space</option>
                        <option value="right_space">Right with space</option>
                    </SelectRow>

                    <SelectRow attrs={getRowAttrs(currency, 'thousand_sep')}
                               onChange={updateCurrencyProp('thousand_sep')}
                               label="Thousand Separator"
                    >
                        <option value=".">.</option>
                        <option value=",">,</option>
                    </SelectRow>

                    <SelectRow attrs={getRowAttrs(currency, 'decimal_sep')}
                               onChange={updateCurrencyProp('decimal_sep')}
                               label="Decimal Separator"
                    >
                        <option value=".">.</option>
                        <option value=",">,</option>
                    </SelectRow>

                    <InputRow attrs={getRowAttrs(currency, 'num_decimals', {min:'0', step:'1', type: 'number', 'data-message': 'Only numeric'})}
                              onChange={updateCurrencyProp('num_decimals')}
                              label='Number of Decimals'
                    />

                    <hr/>

                    <SelectRow attrs={getRowAttrs(currency, 'rounding')}
                               onChange={updateCurrencyProp('rounding')}
                               label="Rounding to the nearest integer"
                               tooltip="To be defined!!!"
                    >
                        <option value="disabled">Disabled</option>
                        <option value="up">Up</option>
                        <option value="down">Down</option>
                        <option value="nearest">Nearest</option>
                    </SelectRow>

                    <SelectRow attrs={getRowAttrs(currency, 'rounding_increment', {disabled: currency.rounding === 'disabled'})}
                               onChange={updateCurrencyProp('rounding_increment')}
                               label="Increment for nearest integer"
                               tooltip="To be defined!!!"
                    >
                        <option value="1">1</option>
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="1000">1000</option>
                    </SelectRow>

                    <InputRow attrs={getRowAttrs(currency, 'auto_subtract', {disabled: currency.rounding === 'disabled', type: 'number', 'data-message': 'Only numeric'})}
                              onChange={updateCurrencyProp('auto_subtract')}
                              label='Autosubtract amount'
                              tooltip="To be defined!!!"/>

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

const getRowAttrs = (currency, prop, attrs={}) => {
    return {
        ...{
            id: "wcml_currency_options_" + prop + "_" + currency.code,
            name: "currency_options[" + prop + "]",
            className: "currency_option_" + prop,
            value: currency[prop]
        },
        ...attrs
    };
}