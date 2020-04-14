import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";
import { StoreProvider } from "easy-peasy";
import initStore from './Store';

document.addEventListener('DOMContentLoaded', function() {
    return;
    const store = initStore({
        activeCurrencies: wcmlMultiCurrency.activeCurrencies,
        allCurrencies: wcmlMultiCurrency.allCurrencies,
        languages: wcmlMultiCurrency.languages,
    });

    ReactDOM.render(
      <StoreProvider store={store}>
        <App/>
      </StoreProvider>,
      document.getElementById('wcml-multicurrency-options')
    );
} );