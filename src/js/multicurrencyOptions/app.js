import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";
import { action, createStore, StoreProvider, computed } from "easy-peasy";

const currencies = [
  {
    code: "USD",
    isDefault: true,
    rate: "1",
    label: "US Dollar",
    formatted: "$99.99",
    languages: { 'en': 1, 'fr': 0 },
    symbol: "$",
  },
  {
    code: "EUR",
    isDefault: false,
    rate: "0,92",
    label: "Euro",
    formatted: "€99.99",
    languages: {'en': 1, 'fr': 1, 'es': 1},
    symbol: "€",
  },
];

const allCurrencies = [
  {code:"USD", label:"US Dollar", symbol:"$"},
  {code:"EUR", label:"Euro", symbol:"€"},
  {code:"BRL", label:"Brazilian Real", symbol:"R$"},
  {code:"GBP", label:"British Pound", symbol:"£"},
  {code:"CHF", label:"Swiss Franc", symbol:"Frs"},
];

const languages = [
  {
    code: "en",
    displayName: 'English',
    flagUrl: 'https://wpml.loc/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png',
    defaultCurrency: 0,
  },
  {
    code: "fr",
    displayName: 'French',
    flagUrl: 'https://wpml.loc/wp-content/plugins/sitepress-multilingual-cms/res/flags/fr.png',
    defaultCurrency: "EUR",
  },
  {
    code: "es",
    displayName: 'Spanish',
    flagUrl: 'https://wpml.loc/wp-content/plugins/sitepress-multilingual-cms/res/flags/es.png',
    defaultCurrency: "EUR",
  },
];

const store = createStore({
  activeCurrencies: currencies,
  allCurrencies: allCurrencies,
  languages: languages,
  setDefaultCurrencyForLang: action((state, data) => {
    const index = state.languages.findIndex(lang => lang.code === data.language);
    const language = state.languages[index];
    language.defaultCurrency = data.currency;
    state.languages[index] = language;
  }),
  enableCurrencyForLang: action((state, data) => {
    const index = state.activeCurrencies.findIndex(currency => currency.code === data.currency);
    const currency = state.activeCurrencies[index];

    const enabled = data.enable ? 1 : 0;
    const language = data.language;
    currency.languages = {
      ...currency.languages,
      language: enabled
    };

    state.activeCurrencies[index] = currency;
  }),
  deleteCurrency: action((state, code) => {
    state.activeCurrencies = state.activeCurrencies.filter(currency => currency.code !== code);
  }),
  modalCurrencyCode: null,
  setModalCurrencyCode: action((state, code) => {
    state.modalCurrencyCode = code;
  }),
  newCurrencies: computed(state => {
    const usedCurrencyCodes = state.activeCurrencies.map(currency => currency.code);
    return state.allCurrencies.filter((currency) => ! usedCurrencyCodes.includes(currency.code));
  }),
});

document.addEventListener('DOMContentLoaded', function() {
  ReactDOM.render(
      <StoreProvider store={store}>
        <App currencies={store.get} languages={languages}/>
      </StoreProvider>,
      document.getElementById('wcml-multicurrency-options')
  );
} );