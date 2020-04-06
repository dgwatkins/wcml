import React from "react";
import ReactDOM from "react-dom";
import Layout from "./UI/Layout";

const currencies = [
  {
    code: "USD",
    default: true,
    rate: "1 USD",
    label: "US Dollar",
  },
  {
    code: "EUR",
    default: false,
    rate: "1,10 USD",
    label: "Euro",
  },
];

document.addEventListener('DOMContentLoaded', function() {
  ReactDOM.render(<Layout currencies={currencies}/>, document.getElementById('wcml-multicurrency-options'));
} );