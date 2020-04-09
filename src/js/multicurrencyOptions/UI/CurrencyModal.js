import React from "react";
import { useState } from "react";
import Modal from 'antd/lib/modal';
import 'antd/lib/modal/style/index.css';
import {useStoreActions, useStoreState} from "easy-peasy";

const CurrencyModal = ({currency}) => {
    const setModalCurrencyCode = useStoreActions(action => action.setModalCurrencyCode);
    const close = () => setModalCurrencyCode(null);

    return <Modal
        visible={true}
        onCancel={close}
    >
        <div className="wcml-dialog" id={'wcml_currency_options_' + currency.code}>
            <div className={['wcml_currency_options', 'wcml-co-dialog']}>
                <form id="wcml_currency_options_form_SOME_CODE">
                    <NewCurrencySelector/>

                    <Gateways/>
                </form>
            </div>
        </div>
    </Modal>

};

export default CurrencyModal;

const NewCurrencySelector = () => {
    const newCurrencies = useStoreState(state => state.newCurrencies);

    return (
        <React.Fragment>
            <label htmlFor="wcml_currency_select_new">Select currency</label>
            <select name="wcml_currency_select_new">
                {
                    newCurrencies.map((currency) => <option key={currency.code} value={currency.code}>{currency.label}</option>)
                }
            </select>
        </React.Fragment>
    );
};

const Gateways = () => {
    const [ checked, setChecked ] = useState('checked');

    return (
        <div>
            <label className="label-header"><strong>Payment Gateways</strong></label>

            <label className="wcml-gateways-switcher">
                <input name="currency_options[gateways_enabled]" type="checkbox"
                       className="wcml-gateways-enabled otgs-switcher-input" checked={checked} onChange={() => setChecked(!checked)}/>
                <span className="otgs-switcher wpml-theme" data-on="ON" data-off="OFF"></span>
                <a className="wpml-external-link"
                   href="https://wpml.org/?page_id=290080&utm_source=wcml-admin&utm_medium=plugin&utm_term=payment-gateways-settings&utm_content=documentation&utm_campaign=WCML#payment-gateways-settings"
                   target="_blank">Learn more</a>
            </label>
        </div>
    );
};
