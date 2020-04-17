import React from "react";
import {useState} from "react";
import Modal from 'antd/lib/modal';
import 'antd/lib/modal/style/index.css';
import {useStoreActions, useStoreState} from "easy-peasy";
import {useStore, getStoreAction, getStoreProperty} from "../Store";
import {validateRate, getFormattedPricePreview} from '../Utils';

const CurrencyModal = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const onClose = () => setModalCurrency(null);
    const [isValidRate, setIsValidRate] = useState(false);
    const onChangeRate = (event) => {
        const rate = event.target.value;
        setModalCurrency({...currency, rate:rate});

        if (validateRate(rate)) {
            setIsValidRate(false);
        } else {
            setIsValidRate(true);
        }
    };

    return <Modal
        visible={true}
        onCancel={onClose}
    >
        <div className="wcml-dialog wcml-dialog-container ui-dialog-content ui-widget-content" id={'wcml_currency_options_' + currency.code}>
            <div className="wcml_currency_options wcml-co-dialog">
                <form id="wcml_currency_options_form_SOME_CODE">
                    {/*<NewCurrencySelector/>*/}
                    <input type="hidden" name="currency_options[code]" value="USD" />

                        <div className="wpml-form-row wcml-co-exchange-rate">
                            <label htmlFor="wcml_currency_options_rate_USD">Exchange Rate</label>
                            <div className="wcml-co-set-rate">
                                1 BRL = <input name="currency_options[rate]" size="5" type="number"
                                               className="wcml-exchange-rate" min="0.01" step="0.01" value={currency.rate}
                                               data-message="Only numeric" id="wcml_currency_options_rate_USD"
                                               onChange={onChangeRate}
                                        />
                                <span className="this-currency">USD</span>
                                {isValidRate && <span className="wcml-error">Please enter a valid number</span>}
                                <small>
                                    {currency.updated} <i></i>
                                </small>
                            </div>
                        </div>

                        <hr/>

                            {/*<div className="wpml-form-row wcml-co-preview">*/}
                            {/*    <label><strong>Currency Preview</strong></label>*/}
                            {/*    <p className="wcml-co-preview-value">*/}
                            {/*        <span className="woocommerce-Price-amount amount"><span*/}
                            {/*            className="woocommerce-Price-currencySymbol">$</span>1,234.00</span>*/}
                            {/*    </p>*/}
                            {/*</div>*/}

                    <PreviewCurrency currency={currency} />

                            <div className="wpml-form-row">
                                <label htmlFor="wcml_currency_options_position_USD">Currency Position</label>
                                <select className="currency_option_position"
                                        name="currency_options[position]"
                                        id="wcml_currency_options_position_USD"
                                        value={currency.position}
                                        onChange={e => setModalCurrency({...currency, position:e.target.value})}
                                >
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                    <option value="left_space">Left with space</option>
                                    <option value="right_space">Right with space</option>
                                </select>
                            </div>

                            <div className="wpml-form-row">
                                <label htmlFor="wcml_currency_options_thousand_USD">Thousand Separator</label>
                                <select className="currency_option_thousand_sep"
                                        name="currency_options[thousand_sep]"
                                        id="wcml_currency_options_thousand_USD"
                                        value={currency.thousand_sep}
                                        onChange={e => setModalCurrency({...currency, thousand_sep:e.target.value})}
                                >
                                    <option value=".">.</option>
                                    <option value=",">,</option>
                                </select>
                            </div>

                            <div className="wpml-form-row">
                                <label htmlFor="wcml_currency_options_decimal_USD">Decimal Separator</label>
                                <select className="currency_option_decimal_sep"
                                        name="currency_options[decimal_sep]"
                                        id="wcml_currency_options_decimal_USD">
                                        value={currency.decimal_sep}
                                        onChange={e => setModalCurrency({...currency, decimal_sep:e.target.value})}
                                    <option value=".">.</option>
                                    <option value=",">,</option>
                                </select>
                            </div>

                            <div className="wpml-form-row">
                                <label htmlFor="wcml_currency_options_decimals_USD">Number of Decimals</label>
                                <input name="currency_options[num_decimals]" type="number"
                                       className="currency_option_decimals" min="0" step="1"
                                       data-message="Only numeric" id="wcml_currency_options_numbers_of_decimals_USD"
                                       value={currency.num_decimals}
                                       onChange={e => setModalCurrency({...currency, num_decimals:e.target.value})}
                                />
                            </div>

                            <hr/>

                                <div className="wpml-form-row">
                                    <label htmlFor="wcml_currency_options_rounding_USD">Rounding to the nearest
                                        integer <i className="wcml-tip otgs-ico-help"></i></label>
                                    <select name="currency_options[rounding]"
                                            id="wcml_currency_options_rounding_USD"
                                            value={currency.rounding}
                                            onChange={e => setModalCurrency({...currency, rounding:e.target.value})}
                                    >
                                        <option value="disabled">Disabled</option>
                                        <option value="up">Up</option>
                                        <option value="down">Down</option>
                                        <option value="nearest">Nearest</option>
                                    </select>
                                </div>

                                <div className="wpml-form-row">
                                    <label htmlFor="wcml_currency_options_increment_USD">Increment for nearest
                                        integer <i className="wcml-tip otgs-ico-help"></i></label>
                                    <select name="currency_options[rounding_increment]"
                                            id="wcml_currency_options_increment_USD"
                                            value={currency.rounding_increment}
                                            onChange={e => setModalCurrency({...currency, rounding_increment:e.target.value})}
                                    >
                                        <option value="1">1</option>
                                        <option value="10">10</option>
                                        <option value="100">100</option>
                                        <option value="1000">1000</option>
                                    </select>
                                </div>

                                <div className="wpml-form-row">
                                    <label htmlFor="wcml_currency_options_subtract_USD">Autosubtract amount <i
                                        className="wcml-tip otgs-ico-help"></i></label>

                                    <input name="currency_options[auto_subtract]"
                                           className="abstract_amount" value="0"
                                           type="number" data-message=""
                                           id="wcml_currency_options_subtract_USD"
                                           value={currency.auto_subtract}
                                           onChange={e => setModalCurrency({...currency, auto_subtract:e.target.value})}
                                    />
                                </div>

                                <hr/>
                    <Gateways/>
                </form>
            </div>
        </div>
    </Modal>

};

export default CurrencyModal;

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
}

const NewCurrencySelector = () => {
    const newCurrencies = useStoreState(state => state.newCurrencies);

    return (
        <React.Fragment>
            <label htmlFor="wcml_currency_select_new">Select currency</label>
            <select name="wcml_currency_select_new">
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
