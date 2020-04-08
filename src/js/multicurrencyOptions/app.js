import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";
import { action, createStore, StoreProvider } from "easy-peasy";

const currencies = [
  {
    code: "USD",
    default: true,
    rate: "1",
    label: "US Dollar",
    formatted: "$99.99",
    languages: ['en'],
    symbol: "$",
  },
  {
    code: "EUR",
    default: false,
    rate: "0,92",
    label: "Euro",
    formatted: "€99.99",
    languages: ['en', 'fr', 'es'],
    symbol: "€",
  },
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
  currencies: currencies,
  languages: languages,
  setDefaultCurrencyForLang: action((state, data) => {
    const index = state.languages.findIndex(lang => lang.code === data.language);
    const language = state.languages[index];
    language.defaultCurrency = data.currency;
    state.languages[index] = language;
  }),
  enableCurrencyForLang: action((state, data) => {
    const index = state.currencies.findIndex(currency => currency.code === data.currency);
    const currency = state.currencies[index];
    if (data.enable) {
      currency.languages = [data.language, ...currency.languages];
    } else {
      currency.languages = currency.languages.filter(code => code !== data.language)
    }
    state.currencies[index] = currency;
  }),
  deleteCurrency: action((state, code) => {
    state.currencies = state.currencies.filter(currency => currency.code !== code);
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