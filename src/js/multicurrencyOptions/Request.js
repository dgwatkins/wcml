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

export const setCurrencyForLang = (isEnabled, currencyCode, languageCode) => {
    return buildPayload(
        'wcml_update_currency_lang',
        {
            value: isEnabled ? 1 : 0,
            code: currencyCode,
            lang: languageCode,
        }
    );
};

export const createAjaxRequest = createAjax;