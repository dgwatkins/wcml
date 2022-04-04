import {createAjax, doAjax, stringify} from "wpml-common-js-source/src/Ajax";

const doAjaxFor = (action, data) => {
    const payload = {
        action,
        nonce: wcmlMultiCurrency.nonce,
        data: JSON.stringify(data)
    };

    return doAjax(payload);
};

export const doAjaxForSetMaxMindKey = (key) => {
    return doAjaxFor('wcml_set_max_mind_key', {MaxMindKey:key});
};

export const doAjaxForGetAutoExchangeRate = (currency) => {
    return doAjaxFor('wcml_get_auto_exchange_rate', {currency});
};

/**
 * @deprecated
 */
const buildPayload = (action, data) => ({
    body: stringify({
        action,
        nonce: wcmlMultiCurrency.nonce,
        data: JSON.stringify(data)
    })
});

/**
 * @deprecated
 */
const buildCurrencyForLangPayload = ({isEnabled, currencyCode, languageCode}) => buildPayload(
    'wcml_update_currency_lang',
    {
        value: isEnabled ? 1 : 0,
        code: currencyCode,
        lang: languageCode,
    }
);

/**
 * @deprecated
 */
const buildDefaultCurrencyForLangPayload = ({languageCode, currencyCode}) => buildPayload(
    'wcml_update_default_currency',
    {
        code: currencyCode,
        lang: languageCode,
    }
);

/**
 * @deprecated
 */
const buildDeleteCurrencyPayload = ({currencyCode}) => buildPayload(
    'wcml_delete_currency',
    {
        code: currencyCode,
    }
);

/**
 * @deprecated
 */
const buildSaveCurrencyPayload = (currency) => buildPayload(
    'wcml_save_currency',
    {
        currency_options: currency,
    }
);

/**
 * @deprecated
 */
const buildSetCurrencyModePayload = (mode) => buildPayload(
    'wcml_set_currency_mode',
    {
        mode: mode,
    }
);

/**
 * @deprecated
 */
const buildSetMaxMindKeyPayload = (MaxMindKey) => buildPayload(
    'wcml_set_max_mind_key',
    {
        MaxMindKey: MaxMindKey,
    }
);

/**
 * @deprecated
 */
const payloadBuilders = {
    currencyForLang: buildCurrencyForLangPayload,
    defaultCurrencyForLang: buildDefaultCurrencyForLangPayload,
    deleteCurrency: buildDeleteCurrencyPayload,
    saveCurrency: buildSaveCurrencyPayload,
    setCurrencyMode: buildSetCurrencyModePayload,
    setMaxMindKey: buildSetMaxMindKeyPayload,
};

/**
 * @deprecated Use `doAjaxFor` instead.
 *
 * `createAjax` uses `useFetch` (a React hook)
 * that prevent use from using it outside the component.
 *
 * We should rather execute the AJAX request inside
 * the store for instance.
 */
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