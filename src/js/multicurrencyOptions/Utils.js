export const capitalize = str => str[0].toUpperCase() + str.slice(1);

export const getFormattedPricePreview = (allCurrencies) => (currency) => {
    return getFormattedPrice(allCurrencies)('1', '234', '0')(currency);
};

export const getSmallFormattedPrice = (allCurrencies) => (currency) => {
    return getFormattedPrice(allCurrencies)('', '99', '9')(currency);
};

const getFormattedPrice = (allCurrencies) => (firstPart, secondPart, thirdPart) => (currency) => {
    const currencyData = allCurrencies.filter(currencyData => currencyData.code === currency.code )[0];

    return getFormatPlaceholder(firstPart, secondPart)(currency.position)
        .replace(/__SYMBOL__/, currencyData.symbol)
        .replace(/__THOUSAND_SEP__/, firstPart ? currency.thousand_sep : '')
        .replace(/__DECIMAL_SEP__/, currency.num_decimals ? currency.decimal_sep : '')
        .replace(/__DECIMALS_NUMBER__/, thirdPart.repeat(currency.num_decimals));
};

const getFormatPlaceholder = (firstPart, secondPart) => (position) => {
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

// @todo: To check
const KEY_EVENTS = {
    DOM_SUBTRACT: 109,
    DOM_DASH: 189,
    DOM_E: 69
};

export const validateRate = (value) => {
    const isPositive = value => value > 0;
    const isNumber = value => !isNaN(parseFloat(value)) && isFinite(value);
    const hasNoInvalidChar = (value) => true; // @todo: To be confirmed with KEY_EVENTS

    return isNumber(value) && isPositive(value) && hasNoInvalidChar(value);
};