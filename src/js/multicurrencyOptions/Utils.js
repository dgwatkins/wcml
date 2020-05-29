/* global wcmlMultiCurrency */
import * as R from 'ramda';

export const capitalize = str => str[0].toUpperCase() + str.slice(1);

export const getFormattedPricePreview = (currency) => {
    return getFormattedPrice('1', '234', '0')(currency);
};

export const getSmallFormattedPrice = (currency) => {
    return getFormattedPrice('', '99', '9')(currency);
};

const getFormattedPrice = (thousands, units, decimals) => (currency) => {
    const currencyData = getCurrencyData(currency.code);

    return getFormatPlaceholder(thousands, units, currency.position)
        .replace(/__SYMBOL__/, currencyData.symbol)
        .replace(/__THOUSAND_SEP__/, thousands ? currency.thousand_sep : '')
        .replace(/__DECIMAL_SEP__/, currency.num_decimals > 0 ? currency.decimal_sep : '')
        .replace(/__DECIMALS_NUMBER__/, decimals.repeat(currency.num_decimals));
};

const getCurrencyData = code => {
    return wcmlMultiCurrency.allCurrencies.filter(currencyData => currencyData.code === code )[0];
};

export const getCurrencyLabel = R.pipe( getCurrencyData, R.prop('label') );

export const getCurrencySymbol = R.pipe( getCurrencyData, R.prop('symbol') );

const getCountryData = code => {
    return wcmlMultiCurrency.allCountries.filter(countryData => countryData.code === code )[0];
};

export const getCountryLabel = R.pipe( getCountryData, R.prop('label') );

const getFormatPlaceholder = (thousands, units, position) => {
    switch(position){
        case 'left':
            return '__SYMBOL__' + thousands + '__THOUSAND_SEP__' + units + '__DECIMAL_SEP____DECIMALS_NUMBER__';
        case 'right':
            return thousands + '__THOUSAND_SEP__' + units + '__DECIMAL_SEP____DECIMALS_NUMBER____SYMBOL__';
        case 'left_space':
            return '__SYMBOL__ ' + thousands + '__THOUSAND_SEP__' + units + '__DECIMAL_SEP____DECIMALS_NUMBER__';
        case 'right_space':
            return thousands + '__THOUSAND_SEP__' + units + '__DECIMAL_SEP____DECIMALS_NUMBER__ __SYMBOL__';
    }
};

export const validateRate = R.allPass( [R.pipe( parseFloat, isNaN, R.not ), isFinite, R.gt( R.__, 0 )] );

export const triggerActiveCurrenciesChange = function(payload) {
    payload.currencyData = getCurrencyData(payload.currency.code);
    document.dispatchEvent(new CustomEvent('wcmlActiveCurrenciesChange', {detail:payload}));
};

export const triggerModeChange = function() {
    document.dispatchEvent(new CustomEvent('wcmlModeChange'));
};