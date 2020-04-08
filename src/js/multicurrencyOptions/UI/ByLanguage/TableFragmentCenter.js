import React from "react";
import {useStoreState} from "easy-peasy";

const TableFragmentCenter = () => {
    const currencies = useStoreState(state => state.currencies);
    const languages  = useStoreState(state => state.languages);

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

                        {currencies.map(currency => <Row key={currency.code} currency={currency} languages={languages} />)}

                        <DefaultRow languages={languages} currencies={currencies} />

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
    const titleEnable  = 'Enable __CURRENCY__ for __LANGUAGE__';
    const titleDisable = 'Disable __CURRENCY__ for __LANGUAGE__';
    const isEnabled = !! currency.languages.includes(language.code);

    const title = ( isEnabled ? titleDisable : titleEnable )
        .replace('__LANGUAGE__', language.displayName)
        .replace('__CURRENCY__', currency.label);

    const linkClass = isEnabled ? "otgs-ico-yes" : "otgs-ico-no";

    return <td className="currency_languages">
                <ul>
                    <li className="on" data-lang={language.code}>
                        <a className={linkClass}
                           data-language={language.code}
                           data-currency={currency.code} href="#"
                           title={title}
                        ></a>
                    </li>
                </ul>
            </td>
};

const DefaultRow = ({languages, currencies}) => {
    return <tr className="default_currency">
                {
                    languages.map((language) => <DefaultCell key={'default-' + language.code} language={language} currencies={currencies} />)
                }
            </tr>
}

const DefaultCell = ({language, currencies}) => {
    const options = currencies
        .filter(currency => {return currency.languages.includes(language.code)})
        .map(currency => {
            return {'text': currency.code, 'value': currency.code};
        })

    const allOptions = [
        {'text': 'Keep', 'value': 0},
        ...options
    ];

    const onChange = () => {};

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