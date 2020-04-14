import {createAjax, stringify} from "wpml-common-js-source/src/Ajax";

const buildPayload = (action, data) => {
    return {
        body: stringify({
            action,
            nonce: wcmlMultiCurrency.nonce,
            data: JSON.stringify(data)
        })
    }
};

const buildCurrencyForLangPayload = ({isEnabled, currencyCode, languageCode}) => {
    return buildPayload(
        'wcml_update_currency_lang',
        {
            value: isEnabled ? 1 : 0,
            code: currencyCode,
            lang: languageCode,
        }
    );
};

const capitalize = str => str[0].toUpperCase() + str.slice(1);

const payloadBuilders = {
    buildCurrencyForLangPayload,
};

export const createAjaxRequest = (endpoint) => {
    const ajax = createAjax();
    const buildPayload = 'build' + capitalize(endpoint) + 'Payload';

    return {
        fetching: ajax.fetching,
        send: data => ajax.doFetch(payloadBuilders[buildPayload](data)),
    };
}