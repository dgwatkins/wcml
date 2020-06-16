import React from "react";
import Select from "antd/lib/select";
import 'antd/dist/antd.css';
import 'antd/lib/select/style/index.css';
import 'antd/lib/tooltip/style/index.css';
import {SelectRow} from "./FormElements";

/**
 * Component for showing countries mode and list of countries
 *
 * @param {array} modeAttrs Attributes for Mode select
 * @param {string} currentMode Current mode
 * @param onChangeMode On change mode callback
 * @param {array} selectCountriesAttrs Attributes for Countries select
 * @param onChangeSelectedCountries On change countires callback
 * @param {array} allCountries List of countries
 * @param {array} strings List of strings
 * @returns {XML}
 * @constructor
 */
export const CountriesFilter = ({modeAttrs, currentMode, onChangeMode, selectCountriesAttrs, onChangeSelectedCountries, allCountries, strings}) => {
    return (
        <React.Fragment>
            <CountriesModeSelect attrs={modeAttrs}
                                 onChange={onChangeMode}
                                 strings={strings}/>
            {currentMode !== 'all' &&
            <CountriesSelect attrs={selectCountriesAttrs}
                             onChange={onChangeSelectedCountries}
                             allCountries={allCountries}
                             label={currentMode === 'exclude' ? strings.labelAllCountriesExcept : strings.labelSpecificCountries}/>
            }
        </React.Fragment>
    );
};

const CountriesSelect = ({attrs, onChange, allCountries, label}) => {

    const countries = [];

    allCountries.map((country, key) => {
        countries.push(<Select.Option key={key} value={country.code} label={country.label}>{country.label}</Select.Option>);
    });
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}</label>
            <Select
                mode="multiple"
                style={{width: '185px'}}
                value={attrs.value}
                onChange={onChange}
                optionLabelProp="label"
                allowClear={true}
            >
                {countries}
            </Select>
            <input type="hidden" name={attrs.name} value={attrs.value}/>
        </div>
    );
};

const CountriesModeSelect = ({attrs, onChange, strings}) => {

    return <SelectRow attrs={attrs}
                       onChange={e => onChange(e.target.value)}
                       label={strings.labelAvailability}
                       tooltip={strings.tooltip}>
                <option value="all">{strings.labelAllCountries}</option>
                <option value="exclude">{strings.labelAllCountriesExceptDots}</option>
                <option value="include">{strings.labelSpecificCountries}</option>
            </SelectRow>;
};