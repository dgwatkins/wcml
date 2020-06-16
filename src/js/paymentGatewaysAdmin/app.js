/* global wcmlPaymentGateways */

import React from "react";
import ReactDOM from "react-dom";
import App from "./UI/App";

document.addEventListener('DOMContentLoaded', function() {
    ReactDOM.render(
        <App gatewayId={wcmlPaymentGateways.gatewayId} allCountries={wcmlPaymentGateways.allCountries} initialSettings={wcmlPaymentGateways.settings} />,
        document.getElementById('wcml-payment-gateways')
    );
} );