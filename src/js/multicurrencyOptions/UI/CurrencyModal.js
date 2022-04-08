import React from "react";
import {useState, useEffect} from "react";
import Modal from 'antd/lib/modal';
import {getPopupContainer} from '../../sharedComponents/helpers';
import Select from "antd/lib/select";
import 'antd/lib/select/style/index.css';
import 'antd/lib/modal/style/index.css';
import 'antd/lib/tooltip/style/index.css';
import {useStore, getStoreProperty, getStoreAction} from "../Store";
import {validateRate, getFormattedPricePreview, getCurrencyLabel, getCurrencySymbol} from '../Utils';
import Gateways from "./Gateways/Gateways";
import {SelectRow, InputRow, Spinner} from "../../sharedComponents/FormElements";
import {CountriesFilter} from "../../sharedComponents/CountriesFilter";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {sprintf} from "wpml-common-js-source/src/i18n";
import * as R from "ramda";

const CurrencyModal = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const saveModalCurrency = getStoreAction('saveModalCurrency');
    const isValidRate = getStoreProperty('isValidModalRate');
    const defaultCurrency = getStoreProperty('defaultCurrency');
    const isStandalone = getStoreProperty('isStandalone');
    const onClose = () => setModalCurrency(null);

    const ajax = createAjaxRequest('saveCurrency');
    const presetExchangeRate = getStoreAction('presetExchangeRate');

    if ( currency.isNew ) {
        useEffect(() => {
            presetExchangeRate(currency.code);
        }, [currency.code]);
    }

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

    const mode = getStoreProperty('mode');

    const updateCurrencyPropValue = (prop) => (value) => {
        setModalCurrency({...currency, [prop]:value});
    };

    return <Modal
        title={modalTitle}
        visible={true}
        onCancel={onClose}
        style={{ top: 30 }}
        footer={<Footer onClose={onClose} onSave={onSave} disableSave={!isValidRate && ajax.fetching} />}
        bodyStyle={{maxHeight:769, overflow:'auto'}}
    >
        <div className="wcml-dialog ui-dialog-content" id={'wcml_currency_options_' + currency.code}>
            <div className="wcml_currency_options wcml-co-dialog">
                <form id={"wcml_currency_options_form_" + currency.code}>

                    {!currency.isDefault && (
                            <CurrencySettingsFields currency={currency}
                                                    setModalCurrency={setModalCurrency}
                                                    defaultCurrency={defaultCurrency}
                            />
                        )
                    }

                    {'by_location' === mode && <CountriesBlock currency={currency} onChange={updateCurrencyPropValue} strings={strings} />}

                    {!isStandalone && <Gateways/>}
                </form>
            </div>
        </div>
    </Modal>
};

export default CurrencyModal;

const CurrencySettingsFields = ({currency, setModalCurrency, defaultCurrency}) => {
    const isPresettingRate = getStoreProperty('isPresettingRate');
    const isValidRate = getStoreProperty('isValidModalRate');

    const updateCurrencyProp = (prop) => (e) => {
        setModalCurrency({...currency, [prop]:e.target.value});
    };

    const updateCurrencyPropValue = (prop) => (value) => {
        setModalCurrency({...currency, [prop]:value});
    };

    const onChangeRate = (e) => {
        updateCurrencyProp('rate')(e);
    };

    const [showCurrencyOptions, setCurrencyOptions] = useState(false);
    const onEditClick = () => setCurrencyOptions(true);

    return (
        <React.Fragment>
            {currency.isNew && <NewCurrencySelector currency={currency.code} updateCurrencyPropValue={updateCurrencyPropValue} />}

            <div className="wpml-form-row wcml-co-exchange-rate">
                <label htmlFor={"wcml_currency_options_rate_" + currency.code}>{strings.labelExchangeRate}</label>
                <div className="wcml-co-set-rate">
                    1 {defaultCurrency.code} = <input name="currency_options[rate]" size="5" type="number"
                                   className="wcml-exchange-rate" min="0.01" step="0.01" value={currency.rate}
                                   data-message={strings.labelOnlyNumeric} id={"wcml_currency_options_rate_" + currency.code}
                                   onChange={onChangeRate}
                                   disabled={isPresettingRate}
                />
                    <span className="this-currency">{currency.code}</span>
                    {isPresettingRate && <Spinner />}
                    {!isValidRate && <React.Fragment><br /><span className="wcml-error">{strings.errorInvalidNumber}</span></React.Fragment>}
                    <small>{currency.formattedLastRateUpdate}</small>
                </div>
            </div>

            <hr/>

            <PreviewCurrency currency={currency} />

            {
                showCurrencyOptions
                    ? <CurrencyOptions currency={currency} updateCurrencyProp={updateCurrencyProp} strings={strings}/>
                    : <a href="#" title={strings.labelEdit} onClick={onEditClick}><i className="otgs-ico-edit" title={strings.labelEdit}/></a>
            }

            <hr/>

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
                <option value=" ">{strings.labelSpaceSep}</option>
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

const CountriesBlock = ({currency, onChange, strings }) => {
    const allCountries = getStoreProperty('allCountries');

    return (
        <React.Fragment>
            <CountriesFilter modeAttrs={getRowAttrs(currency, 'location_mode')}
                             currentMode={currency.location_mode}
                             onChangeMode={onChange('location_mode')}
                             selectCountriesAttrs={getRowAttrs(currency, 'countries')}
                             onChangeSelectedCountries={onChange('countries')}
                             allCountries={allCountries}
                             strings={strings}/>
            <hr/>
        </React.Fragment>
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

const NewCurrencySelector = ({currency, updateCurrencyPropValue}) => {
    const newCurrencies = getStoreProperty('newCurrencies');

    const currencies = newCurrencies.map(currency => {
        return <Select.Option key={currency.code} value={currency.code} label={currency.label}>{currency.label}</Select.Option>;
    });

    return (
        <div className="wpml-form-row currency_code">
            <label htmlFor="wcml_currency_select_">{strings.labelSelectCurrency}</label>
            <Select showSearch
                    style={{width: '185px'}}
                    value={currency}
                    onChange={updateCurrencyPropValue('code')}
                    name="currency_options[code]"
                    id="wcml_currency_options_code_"
                    optionLabelProp="label"
                    optionFilterProp="label"
                    allowClear={false}
                    getPopupContainer={getPopupContainer}
            >
                {currencies}
            </Select>
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
