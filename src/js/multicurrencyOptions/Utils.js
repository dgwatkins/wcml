/* global wcmlMultiCurrency */

export const capitalize = str => str[0].toUpperCase() + str.slice(1);

export const getFormattedPricePreview = (currency) => {
    return getFormattedPrice('1', '234', '0')(currency);
};

export const getSmallFormattedPrice = (currency) => {
    return getFormattedPrice('', '99', '9')(currency);
};

const getFormattedPrice = (firstPart, secondPart, thirdPart) => (currency) => {
    const currencyData = getCurrencyData(currency.code);

    return getFormatPlaceholder(firstPart, secondPart, currency.position)
        .replace(/__SYMBOL__/, currencyData.symbol)
        .replace(/__THOUSAND_SEP__/, firstPart ? currency.thousand_sep : '')
        .replace(/__DECIMAL_SEP__/, currency.num_decimals > 0 ? currency.decimal_sep : '')
        .replace(/__DECIMALS_NUMBER__/, thirdPart.repeat(currency.num_decimals));
};

export const getCurrencyLabel = code => getCurrencyData(code).label;

export const getCurrencySymbol = code => getCurrencyData(code).symbol;

const getCurrencyData = code => {
    return wcmlMultiCurrency.allCurrencies.filter(currencyData => currencyData.code === code )[0];
};

const getFormatPlaceholder = (firstPart, secondPart, position) => {
    switch(position){
        case 'left':
            return '__SYMBOL__' + firstPart + '__THOUSAND_SEP__' + secondPart + '__DECIMAL_SEP____DECIMALS_NUMBER__';
        case 'right':
            return firstPart + '__THOUSAND_SEP__' + secondPart + '__DECIMAL_SEP____DECIMALS_NUMBER____SYMBOL__';
        case 'left_space':
            return '__SYMBOL__ ' + firstPart + '__THOUSAND_SEP__' + secondPart + '__DECIMAL_SEP____DECIMALS_NUMBER__';
        case 'right_space':
            return firstPart + '__THOUSAND_SEP__' + secondPart + '__DECIMAL_SEP____DECIMALS_NUMBER__ __SYMBOL__';
    }
};

export const validateRate = (value) => {
    const isPositive = value => value > 0;
    const isNumber = value => !isNaN(parseFloat(value)) && isFinite(value);

    return isNumber(value) && isPositive(value);
};

export const triggerActiveCurrenciesChange = function(payload) {
    payload.currencyData = getCurrencyData(payload.currency.code);
    document.dispatchEvent(new CustomEvent('wcmlActiveCurrenciesChange', {detail:payload}));
};