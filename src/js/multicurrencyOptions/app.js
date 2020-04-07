import React from "react";
import ReactDOM from "react-dom";
import Layout from "./UI/Layout";

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

document.addEventListener('DOMContentLoaded', function() {
  ReactDOM.render(<Layout currencies={currencies} languages={languages}/>, document.getElementById('wcml-multicurrency-options'));
} );