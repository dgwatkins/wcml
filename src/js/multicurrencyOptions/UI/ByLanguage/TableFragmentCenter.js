import React from "react";
import {useStoreState, useStoreActions} from "easy-peasy";

const TableFragmentCenter = () => {
    const activeCurrencies = useStoreState(state => state.activeCurrencies);
    const languages = useStoreState(state => state.languages);

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
    const enableCurrencyForLang = useStoreActions(actions => actions.enableCurrencyForLang);

    const titleEnable  = 'Enable __CURRENCY__ for __LANGUAGE__';
    const titleDisable = 'Disable __CURRENCY__ for __LANGUAGE__';
    const isEnabled    = !! currency.languages[language.code];

    const title = ( isEnabled ? titleDisable : titleEnable )
        .replace('__LANGUAGE__', language.displayName)
        .replace('__CURRENCY__', currency.label);

    const linkClass = isEnabled ? "otgs-ico-yes" : "otgs-ico-no";

    const onClick = () => enableCurrencyForLang({enable:!isEnabled, currency:currency.code, language:language.code});

    return <td className="currency_languages">
                <ul>
                    <li className="on" data-lang={language.code}>
                        <a className={linkClass}
                           data-language={language.code}
                           data-currency={currency.code} href="#"
                           title={title}
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
}

const DefaultCell = ({language, activeCurrencies}) => {
    const setDefaultCurrencyForLang = useStoreActions(actions => actions.setDefaultCurrencyForLang);

    const options = activeCurrencies
        .filter(currency => {return !! currency.languages[language.code]})
        .map(currency => {
            return {'text': currency.code, 'value': currency.code};
        })

    const allOptions = [
        {'text': 'Keep', 'value': 0},
        ...options
    ];

    const onChange = event => setDefaultCurrencyForLang({language:language.code, currency:event.target.value});

    return <td align="center">
                <select value={language.defaultCurrency} onChange={onChange} rel={language.code}>
                    {
                        allOptions.map(option =>
                            <option key={'default-' + option.value} value={option.value}>{option.text}</option>
                        )
                    }
                </select>
            </td>
};