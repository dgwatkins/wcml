import React from "react";
import {useStore, getStoreAction, getStoreProperty} from "../../Store";
import {createAjaxRequest} from "../../Request";

const TableFragmentCenter = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');
    const languages = getStoreProperty('languages');

    return <div className="currency_wrap">
                <div className="currency_inner">
                    <table className="widefat currency_lang_table" id="currency-lang-table">
                            <thead>
                                <tr>
                                    <td colSpan={languages.length}>Currencies to display for each language</td>
                                </tr>
                                <tr className="currency-lang-flags">
                                    {
                                        languages.map((language) => (
                                                <th key={language.code}>
                                                    <img src={language.flagUrl} alt="language flag" width="18" height="12"/>
                                                </th>
                                            )
                                        )
                                    }
                                </tr>
                            </thead>
                        <tbody>

                        {activeCurrencies.map(currency => <Row key={currency.code} currency={currency} languages={languages} />)}

                        <DefaultRow languages={languages} activeCurrencies={activeCurrencies} />

                        </tbody>
                    </table>
                    <input type="hidden" id="wcml_update_default_currency_nonce"
                           value="form.wpdate_default_cur_nonce"/>

                </div>
            </div>
}

export default TableFragmentCenter;

const Row = ({currency, languages}) => {
  return <tr id={'currency_row_langs_' + currency.code} className="wcml-row-currency-lang">
            {
               languages.map(language => <Cell key={language.code} language={language} currency={currency} />)
            }
        </tr>
};

const Cell = ({language, currency}) => {
    const enableCurrencyForLang = getStoreAction('enableCurrencyForLang');
    const [updating, setUpdating] = useStore('updating');

    const ajax         = createAjaxRequest('currencyForLang');
    const titleEnable  = 'Enable __CURRENCY__ for __LANGUAGE__';
    const titleDisable = 'Disable __CURRENCY__ for __LANGUAGE__';
    const isEnabled    = currency.languages[language.code] != 0 ? true : false;

    const title = ( isEnabled ? titleDisable : titleEnable )
        .replace('__LANGUAGE__', language.displayName)
        .replace('__CURRENCY__', currency.label);

    const linkClass = ajax.fetching ? 'spinner' : (isEnabled ? "otgs-ico-yes" : "otgs-ico-no");
    const linkStyle = ajax.fetching ? {visibility: 'visible', margin: 0} : {};

    const onClick = async (event) => {
        event.preventDefault();

        if (updating) {
            return;
        }

        setUpdating(true);
        const result = await ajax.send({isEnabled:!isEnabled, currencyCode:currency.code, languageCode:language.code});

        if (result.data && result.data.success) {
            enableCurrencyForLang({enable:!isEnabled, currency:currency.code, language:language.code});
        }

        setUpdating(false);
    };

    return <td className="currency_languages">
                <ul>
                    <li className="on" data-lang={language.code}>
                        <a className={linkClass}
                           data-language={language.code}
                           data-currency={currency.code} href="#"
                           title={title}
                           style={linkStyle}
                           onClick={onClick}
                        ></a>
                    </li>
                </ul>
            </td>
};

const DefaultRow = ({languages, activeCurrencies}) => {
    return <tr className="default_currency">
                {
                    languages.map((language) => <DefaultCell key={'default-' + language.code} language={language} activeCurrencies={activeCurrencies} />)
                }
            </tr>
};

const DefaultCell = ({language, activeCurrencies}) => {
    const setDefaultCurrencyForLang = getStoreAction('setDefaultCurrencyForLang');
    const [updating, setUpdating] = useStore('updating');

    const ajax = createAjaxRequest('defaultCurrencyForLang');

    const options = activeCurrencies
        .filter(currency => 1 == currency.languages[language.code])
        .map(currency => {
            return {'text': currency.code, 'value': currency.code};
        })

    const allOptions = [
        {'text': 'Keep', 'value': 0},
        ...options
    ];

    const onChange = async (event) => {
        event.preventDefault();

        if (updating) {
            return;
        }

        const currencyCode = event.target.value;
        const previousValue = language.defaultCurrency;

        setDefaultCurrencyForLang({language: language.code, currency: currencyCode});

        setUpdating(true);
        const result = await ajax.send({languageCode: language.code, currencyCode: currencyCode});

        if (!result.data || !result.data.success) {
            setDefaultCurrencyForLang({language: language.code, currency: previousValue});
        }

        setUpdating(false);
    }

    return <td align="center">
                <select value={language.defaultCurrency} onChange={onChange} rel={language.code}>
                    {
                        allOptions.map(option =>
                            <option key={'default-' + option.value} value={option.value}>{option.text}</option>
                        )
                    }
                </select>
                {ajax.fetching && <span className="spinner" style={{visibility:'visible', float:'none', position:'absolute'}}></span>}
            </td>
};