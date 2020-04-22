import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";
import { StoreProvider } from "easy-peasy";
import initStore from './Store';

document.addEventListener('DOMContentLoaded', function() {
    const store = initStore({
        activeCurrencies: wcmlMultiCurrency.activeCurrencies,
        allCurrencies: wcmlMultiCurrency.allCurrencies,
        languages: wcmlMultiCurrency.languages,
        gateways: wcmlMultiCurrency.gateways,
    });

    ReactDOM.render(
      <StoreProvider store={store}>
        <App/>
      </StoreProvider>,
      document.getElementById('wcml-multicurrency-options')
    );
} );