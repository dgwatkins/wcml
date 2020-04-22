import { action, createStore, useStoreState, useStoreActions, computed, thunk } from "easy-peasy";
import {capitalize} from "./Utils";
import * as R from 'ramda';

const initStore = ({activeCurrencies, allCurrencies, languages, gateways}) => createStore({
    activeCurrencies: activeCurrencies,
    allCurrencies: allCurrencies,
    languages: languages,
    gateways: gateways,
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

        currency.languages = {
            ...currency.languages,
            [data.language]: enabled
        };

        state.activeCurrencies[index] = currency;
    }),
    deleteCurrency: action((state, code) => {
        state.activeCurrencies = state.activeCurrencies.filter(currency => currency.code !== code);
    }),
    modalCurrency: null,
    setModalCurrency: action((state, currency) => {
        state.modalCurrency = currency;
    }),
    newCurrencies: computed(state => {
        const usedCurrencyCodes = state.activeCurrencies.map(currency => currency.code);
        return state.allCurrencies.filter((currency) => ! usedCurrencyCodes.includes(currency.code));
    }),

    updating: false,
    setUpdating: action((state, updating) => {
        state.updating = updating;
    }),
});

export default initStore;

export const getStoreProperty = path => useStoreState(R.path(Array.isArray(path) ? path : [path]));
export const getStoreAction = action => useStoreActions(R.prop(action));
export const getStore = (path, action) => [getStoreProperty(path), getStoreAction(action)];

export const useStore = (item) => [getStoreProperty(item), getStoreAction('set' + capitalize(item)), getStoreAction('reset' + capitalize(item))];