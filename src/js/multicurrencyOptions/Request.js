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

const buildDefaultCurrencyForLangPayload = ({languageCode, currencyCode}) => {
    return buildPayload(
        'wcml_update_default_currency',
        {
            code: currencyCode,
            lang: languageCode,
        }
    );
};

const buildDeleteCurrencyPayload = ({currencyCode}) => {
    return buildPayload(
        'wcml_delete_currency',
        {
            code: currencyCode,
        }
    );
};

const buildSaveCurrencyPayload = (currency) => {
    return buildPayload(
        'wcml_save_currency',
        {
            currency_options: currency,
        }
    );
};

const payloadBuilders = {
    currencyForLang: buildCurrencyForLangPayload,
    defaultCurrencyForLang: buildDefaultCurrencyForLangPayload,
    deleteCurrency: buildDeleteCurrencyPayload,
    saveCurrency: buildSaveCurrencyPayload,
};

export const createAjaxRequest = (endpoint) => {
    if (!payloadBuilders[endpoint]) {
        throw new Error('The endpoint ' + endpoint + ' is not defined');
    }

    const ajax = createAjax();

    return {
        fetching: ajax.fetching,
        send: data => ajax.doFetch(payloadBuilders[endpoint](data)),
    };
}