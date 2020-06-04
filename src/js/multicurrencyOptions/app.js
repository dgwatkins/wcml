/* global wcmlMultiCurrency */

import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";
import { StoreProvider } from "easy-peasy";
import initStore from './Store';

document.addEventListener('DOMContentLoaded', function() {
    const store = initStore({
        activeCurrencies: wcmlMultiCurrency.activeCurrencies,
        allCurrencies: wcmlMultiCurrency.allCurrencies,
        allCountries: wcmlMultiCurrency.allCountries,
        languages: wcmlMultiCurrency.languages,
        gateways: wcmlMultiCurrency.gateways,
        mode: wcmlMultiCurrency.mode,
        maxMindKeyExist: wcmlMultiCurrency.maxMindKeyExist,
    });

    ReactDOM.render(
      <StoreProvider store={store}>
        <App/>
      </StoreProvider>,
      document.getElementById('wcml-multicurrency-options')
    );
} );