import { action, createStore, useStoreState, computed, thunk } from "easy-peasy";

const activeCurrencies = [
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

const initStore = ({activeCurrencies, allCurrencies, languages}) => createStore({
    activeCurrencies: activeCurrencies,
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

        currency.languages = {
            ...currency.languages,
            [data.language]: enabled
        };

        state.activeCurrencies[index] = currency;
    }),
    test: thunk(async (actions, data) => {
        actions.setUpdatingCurrencyByLang({updating:true, currency:data.currency, language:data.language});

        await new Promise((resolve) => {
            setTimeout(() => {
                resolve(console.log('updating currency by lang'));
            }, 2000);
        });

        actions.enableCurrencyForLang(data);
        actions.setUpdatingCurrencyByLang({updating:false, currency:data.currency, language:data.language});
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


    updatingCurrencyByLang: {},
    setUpdatingCurrencyByLang: action((state, payload) => {
        const {updating, currency, language} = payload;
        const key = currency + '-' + language;

        if (updating) {
            state.updatingCurrencyByLang[key] = true;
        } else {
            delete state.updatingCurrencyByLang[key];
        }
    }),
    isUpdatingCurrencyByLang: (currency, language) => computed(state => state.updatingCurrencyByLang[currency + '-' + language]),
});

export default initStore;

export const isUpdating = (object) => {
    const updating = useStoreState(state => state.updating);

    return !! updating[getObjectKey(object)];
};