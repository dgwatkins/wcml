import { action, createStore, useStoreState, useStoreActions, computed, thunk } from "easy-peasy";
import {capitalize, triggerActiveCurrenciesChange} from "./Utils";
import * as R from 'ramda';

const initStore = ({activeCurrencies, allCurrencies, languages, gateways}) => createStore({
    activeCurrencies: activeCurrencies,
    allCurrencies: allCurrencies,
    languages: languages,
    gateways: gateways,
    modalCurrency: null,
    updating: false,

    setDefaultCurrencyForLang: action((state, data) => {
        const index = state.languages.findIndex(lang => lang.code === data.language);
        const language = state.languages[index];
        language.defaultCurrency = data.currency;
        state.languages[index] = language;
    }),

    getCurrencyIndex: action((state, code) => {
        state.activeCurrencies.findIndex(currency => currency.code === code);
    }),

    enableCurrencyForLang: action((state, data) => {
        const index = getCurrencyIndex(state.activeCurrencies)(data.currency);
        const currency = state.activeCurrencies[index];
        const enabled = data.enable ? 1 : 0;

        currency.languages = {
            ...currency.languages,
            [data.language]: enabled
        };

        state.activeCurrencies[index] = currency;
    }),

    deleteCurrency: action((state, code) => {
        const index = getCurrencyIndex(state.activeCurrencies)(code);
        const removedCurrency = {...state.activeCurrencies[index]};
        state.activeCurrencies.splice(index, 1);
        triggerActiveCurrenciesChange({action:'remove', currency:removedCurrency});
    }),

    setModalCurrency: action((state, currency) => {
        state.modalCurrency = currency;
    }),

    saveModalCurrency: action(state => {
        const index = getCurrencyIndex(state.activeCurrencies)(state.modalCurrency.code);

        if (index < 0) {
            const newCurrency = {...state.modalCurrency, isNew:false};
            state.activeCurrencies.push(newCurrency);
            triggerActiveCurrenciesChange({action:'add', currency:newCurrency});
        } else {
            state.activeCurrencies[index] = state.modalCurrency;
        }
    }),

    newCurrencies: computed(state => {
        const usedCurrencyCodes = state.activeCurrencies.map(currency => currency.code);
        return state.allCurrencies.filter((currency) => ! usedCurrencyCodes.includes(currency.code));
    }),

    defaultCurrency: computed(state => {
        return state.activeCurrencies.filter(currency => currency.isDefault)[0];
    }),

    setUpdating: action((state, updating) => {
        state.updating = updating;
    }),
});

export default initStore;

export const getCurrencyIndex = currencies => code => R.findIndex(R.propEq('code', code))(currencies);

export const getStoreProperty = path => useStoreState(R.path(Array.isArray(path) ? path : [path]));
export const getStoreAction = action => useStoreActions(R.prop(action));
export const getStore = (path, action) => [getStoreProperty(path), getStoreAction(action)];

export const useStore = (item) => [getStoreProperty(item), getStoreAction('set' + capitalize(item)), getStoreAction('reset' + capitalize(item))];