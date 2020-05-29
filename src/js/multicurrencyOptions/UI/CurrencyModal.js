import React from "react";
import {useState} from "react";
import Modal from 'antd/lib/modal';
import Select from "antd/lib/select";
import 'antd/dist/antd.css';
import 'antd/lib/select/style/index.css';
import 'antd/lib/modal/style/index.css';
import 'antd/lib/tooltip/style/index.css';
import {useStore, getStoreProperty, getStoreAction} from "../Store";
import {validateRate, getFormattedPricePreview, getCurrencyLabel, getCurrencySymbol} from '../Utils';
import Gateways from "./Gateways/Gateways";
import {SelectRow, InputRow} from "./FormElements";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {sprintf} from "wpml-common-js-source/src/i18n";
import * as R from "ramda";

const CurrencyModal = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const saveModalCurrency = getStoreAction('saveModalCurrency');
    const [isValidRate, setIsValidRate] = useState(true);
    const defaultCurrency = getStoreProperty('defaultCurrency');
    const onClose = () => setModalCurrency(null);

    const ajax = createAjaxRequest('saveCurrency');

    const onSave = async () => {
        const result = await ajax.send(currency);

        if (result) {
            const formattedLastRateUpdate = R.path(['data', 'data', 'formattedLastRateUpdate'], result);
            setModalCurrency({...currency, formattedLastRateUpdate})
            saveModalCurrency();
            onClose();
        }
    };

    const modalTitle = currency.isNew
        ? strings.labelAddNewCurrency
        : sprintf(strings.placeholderCurrencySettingsFor, getCurrencyLabel(currency.code) + ' (' + getCurrencySymbol(currency.code) + ')');

    return <Modal
        title={modalTitle}
        visible={true}
        onCancel={onClose}
        footer={<Footer onClose={onClose} onSave={onSave} disableSave={!isValidRate && ajax.fetching} />}
        bodyStyle={{maxHeight:769, height:575, overflow:'auto'}}
    >
        <div className="wcml-dialog wcml-dialog-container ui-dialog-content ui-widget-content" id={'wcml_currency_options_' + currency.code}>
            <div className="wcml_currency_options wcml-co-dialog">
                <form id={"wcml_currency_options_form_" + currency.code}>

                    {!currency.isDefault && (
                            <CurrencySettingsFields currency={currency}
                                                    isValidRate={isValidRate}
                                                    setIsValidRate={setIsValidRate}
                                                    setModalCurrency={setModalCurrency}
                                                    defaultCurrency={defaultCurrency}
                            />
                        )
                    }

                    <Gateways/>
                </form>
            </div>
        </div>
    </Modal>
};

export default CurrencyModal;

const CurrencySettingsFields = ({currency, isValidRate, setIsValidRate, setModalCurrency, defaultCurrency}) => {
    const updateCurrencyProp = (prop) => (e) => {
        setModalCurrency({...currency, [prop]:e.target.value});
    };

    const updateCountries = (e) => {
        setModalCurrency({...currency, ['countries']:e});
    };

    const onChangeRate = (e) => {
        updateCurrencyProp('rate')(e);

        if (validateRate(e.target.value)) {
            setIsValidRate(true);
        } else {
            setIsValidRate(false);
        }
    };

    const [showCurrencyOptions, setCurrencyOptions] = useState(false);
    const onEditClick = () => setCurrencyOptions(true);

    const allCountries = getStoreProperty('allCountries');
    const mode = getStoreProperty('mode');
    const countries = [];

    allCountries.map((country, key) => {
        countries.push(<Select.Option key={key} value={country.code} label={country.label}>{country.label}</Select.Option>);
    });

    return (
        <React.Fragment>
            {currency.isNew && <NewCurrencySelector updateCurrencyProp={updateCurrencyProp} />}

            <div className="wpml-form-row wcml-co-exchange-rate">
                <label htmlFor={"wcml_currency_options_rate_" + currency.code}>{strings.labelExchangeRate}</label>
                <div className="wcml-co-set-rate">
                    1 {defaultCurrency.code} = <input name="currency_options[rate]" size="5" type="number"
                                   className="wcml-exchange-rate" min="0.01" step="0.01" value={currency.rate}
                                   data-message={strings.labelOnlyNumeric} id={"wcml_currency_options_rate_" + currency.code}
                                   onChange={onChangeRate}
                />
                    <span className="this-currency">{currency.code}</span>
                    {!isValidRate && <span className="wcml-error">{strings.errorInvalidNumber}</span>}
                    <small>{currency.formattedLastRateUpdate}</small>
                </div>
            </div>

            <hr/>

            <PreviewCurrency currency={currency} />

            {!showCurrencyOptions && <a href="#" title={strings.labelEdit} onClick={onEditClick}><i className="otgs-ico-edit" title={strings.labelEdit} /></a>}
            {showCurrencyOptions && <CurrencyOptions currency={currency} updateCurrencyProp={updateCurrencyProp} strings={strings}/> }

            <hr/>

            {'by_location' === mode && <CountriesBlock currency={currency} onChangeMode={updateCurrencyProp('location_mode')} onChangeCountries={updateCountries} countries={countries} strings={strings} />}

        </React.Fragment>
    );
};

const CurrencyOptions = ({currency, updateCurrencyProp, strings}) => {
    return (
        <React.Fragment>
            <SelectRow attrs={getRowAttrs(currency, 'position')}
                       onChange={updateCurrencyProp('position')}
                       label={strings.labelPosition}
            >
                <option value="left">{strings.optionLeft}</option>
                <option value="right">{strings.optionRight}</option>
                <option value="left_space">{strings.optionLeftSpace}</option>
                <option value="right_space">{strings.optionRightSpace}</option>
            </SelectRow>

            <SelectRow attrs={getRowAttrs(currency, 'thousand_sep')}
                       onChange={updateCurrencyProp('thousand_sep')}
                       label={strings.labelThousandSep}
            >
                <option value=".">.</option>
                <option value=",">,</option>
            </SelectRow>

            <SelectRow attrs={getRowAttrs(currency, 'decimal_sep')}
                       onChange={updateCurrencyProp('decimal_sep')}
                       label={strings.labelDecimalSep}
            >
                <option value=".">.</option>
                <option value=",">,</option>
            </SelectRow>

            <InputRow attrs={getRowAttrs(currency, 'num_decimals', {min:'0', step:'1', type: 'number', 'data-message': strings.labelOnlyNumeric})}
                      onChange={updateCurrencyProp('num_decimals')}
                      label={strings.labelNumDecimals}
            />

            <hr/>

            <SelectRow attrs={getRowAttrs(currency, 'rounding')}
                       onChange={updateCurrencyProp('rounding')}
                       label={strings.labelRounding}
                       tooltip={strings.tooltipRounding}
            >
                <option value="disabled">{strings.optionDisabled}</option>
                <option value="up">{strings.optionUp}</option>
                <option value="down">{strings.optionDown}</option>
                <option value="nearest">{strings.optionNearest}</option>
            </SelectRow>

            <SelectRow attrs={getRowAttrs(currency, 'rounding_increment', {disabled: currency.rounding === 'disabled'})}
                       onChange={updateCurrencyProp('rounding_increment')}
                       label={strings.labelIncrement}
                       tooltip={strings.tooltipIncrement}
            >
                <option value="1">1</option>
                <option value="10">10</option>
                <option value="100">100</option>
                <option value="1000">1000</option>
            </SelectRow>

            <InputRow attrs={getRowAttrs(currency, 'auto_subtract', {disabled: currency.rounding === 'disabled', type: 'number', 'data-message': strings.labelOnlyNumeric})}
                      onChange={updateCurrencyProp('auto_subtract')}
                      label={strings.labelAutosubtract}
                      tooltip={strings.tooltipAutosubtract}
            />
        </React.Fragment>
    );
};

const CountriesBlock = ({currency, onChangeMode, onChangeCountries, countries, strings }) => {
    return (
        <React.Fragment>
            <SelectRow attrs={getRowAttrs(currency, 'location_mode')}
                       onChange={onChangeMode}
                       label={strings.labelCurrencyAvailableIn}>
                <option value="1">{strings.labelAllCountries}</option>
                <option value="2">{strings.labelAllCountriesExcept}...</option>
                <option value="3">{strings.labelSpecificCountries}</option>
            </SelectRow>
            {currency.location_mode !== '1' &&
            <SelectCountries attrs={getRowAttrs(currency, 'countries')}
                             onChange={onChangeCountries}
                             countries={countries}
                             label={currency.location_mode === '2' ? strings.labelAllCountriesExcept : strings.labelSpecificCountries}/>
            }
            <hr/>
        </React.Fragment>
    );
};

const SelectCountries = ({attrs, onChange, countries, label}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}</label>
            <Select
                mode="multiple"
                style={{width: '145px'}}
                value={attrs.value}
                onChange={onChange}
                optionLabelProp="label"
                allowClear={true}
            >
                {countries}
            </Select>
        </div>
    );
};

const Footer = ({onClose, onSave, disableSave}) => {
    return (
        <footer className="wpml-dialog-footer">
            <input type="button"
                   className="cancel wcml-dialog-close-button wpml-dialog-close-button alignleft"
                   onClick={onClose}
                   value={strings.labelCancel}
            />
            <input type="submit"
                   className="wcml-dialog-close-button wpml-dialog-close-button button-primary currency_options_save alignright"
                   onClick={onSave}
                   disabled={disableSave}
                   value={strings.labelSave}
            />
        </footer>
    );
};

const PreviewCurrency = ({currency}) => {
    return (
        <div className="wpml-form-row wcml-co-preview">
            <label><strong>{strings.labelCurrencyPreview}</strong></label>
            <p className="wcml-co-preview-value">
                <span className="woocommerce-Price-amount amount">
                    {getFormattedPricePreview(currency)}
                </span>
            </p>
        </div>
    );
};

const NewCurrencySelector = ({updateCurrencyProp}) => {
    const newCurrencies = getStoreProperty('newCurrencies');

    return (
        <div className="wpml-form-row currency_code">
            <label htmlFor="wcml_currency_select_">{strings.labelSelectCurrency}</label>
            <select name="currency_options[code]"
                    id="wcml_currency_options_code_"
                    onChange={updateCurrencyProp('code')}
            >
                {
                    newCurrencies.map((currency) => <option key={currency.code} value={currency.code}>{currency.label}</option>)
                }
            </select>
        </div>
    );
};

const getRowAttrs = (currency, prop, attrs={}) => {
    return {
        id: "wcml_currency_options_" + prop + "_" + currency.code,
        name: "currency_options[" + prop + "]",
        className: "currency_option_" + prop,
        value: currency[prop],
        ...attrs
    };
}