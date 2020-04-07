import {createAjax, stringify} from "wpml-common-js-source/src/Ajax";

const buildPayload = (action, data) => ({
    body: stringify({
        action,
        nonce: wcmlMultiCurrency.nonce,
        data: JSON.stringify(data)
    })
});

const buildCurrencyForLangPayload = ({isEnabled, currencyCode, languageCode}) => buildPayload(
    'wcml_update_currency_lang',
    {
        value: isEnabled ? 1 : 0,
        code: currencyCode,
        lang: languageCode,
    }
);


const buildDefaultCurrencyForLangPayload = ({languageCode, currencyCode}) => buildPayload(
    'wcml_update_default_currency',
    {
        code: currencyCode,
        lang: languageCode,
    }
);

const buildDeleteCurrencyPayload = ({currencyCode}) => buildPayload(
    'wcml_delete_currency',
    {
        code: currencyCode,
    }
);

const buildSaveCurrencyPayload = (currency) => buildPayload(
    'wcml_save_currency',
    {
        currency_options: currency,
    }
);

const buildSetCurrencyModePayload = (mode) => buildPayload(
    'wcml_set_currency_mode',
    {
        mode: mode,
    }
);

const buildSetMaxMindKeyPayload = (MaxMindKey) => buildPayload(
    'wcml_set_max_mind_key',
    {
        MaxMindKey: MaxMindKey,
    }
);

const payloadBuilders = {
    currencyForLang: buildCurrencyForLangPayload,
    defaultCurrencyForLang: buildDefaultCurrencyForLangPayload,
    deleteCurrency: buildDeleteCurrencyPayload,
    saveCurrency: buildSaveCurrencyPayload,
    setCurrencyMode: buildSetCurrencyModePayload,
    setMaxMindKey: buildSetMaxMindKeyPayload,
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