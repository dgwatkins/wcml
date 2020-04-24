import React, {useState} from "react";
import {useStore, getStoreProperty} from "../../Store";
import {assocPath} from "ramda";
import Bacs from "./Bacs";
import PayPal from "./PayPal";
import Stripe from "./Stripe";
import Unsupported from "./Unsupported";
import {getTooltip} from "../FormElements";

const Gateways = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const gateways = getStoreProperty('gateways');
    const activeCurrencies = getStoreProperty('activeCurrencies');

    return (
        <div>
            <label className="label-header"><strong>Payment Gateways</strong></label>

            <label className="wcml-gateways-switcher">
                <input name="currency_options[gateways_enabled]" type="checkbox"
                       className="wcml-gateways-enabled otgs-switcher-input"
                       checked={currency.gatewaysEnabled}
                       onChange={e => setModalCurrency({...currency, ['gatewaysEnabled']: !currency.gatewaysEnabled})}/>
                <span className="otgs-switcher wpml-theme" data-on="ON" data-off="OFF"/>
                <a className="wpml-external-link"
                   href="https://wpml.org/?page_id=290080&utm_source=wcml-admin&utm_medium=plugin&utm_term=payment-gateways-settings&utm_content=documentation&utm_campaign=WCML#payment-gateways-settings"
                   target="_blank">Learn more</a>
            </label>

            {
                currency.gatewaysEnabled && (
                    <div className='wcml-gateways'>
                        {
                            gateways.map((gateway, key) => {
                                return <Gateway key={key}
                                                gateway={gateway}
                                                settings={currency.gatewaysSettings[gateway.id] || {}}
                                                activeCurrencies={activeCurrencies}
                                                setModalCurrency={setModalCurrency}
                                                currency={currency}
                                />;
                            })
                        }
                    </div>
                )
            }
        </div>
    );
};

export default Gateways;

const Gateway = ({gateway, currency, setModalCurrency, ...attrs}) => {
    const updateSettings = prop => e => {
        setModalCurrency(
            assocPath(
                ['gatewaysSettings', gateway.id, prop],
                e.target.value,
                currency
            )
        );
    };

    const getName = name => 'currency_options[gateways_settings][' + gateway.id + '][' + name + ']'

    let gatewayUi = <Unsupported/>;
    let tooltip = '';

    switch (gateway.id) {
        case 'bacs':
            tooltip = 'TO BE DEFINED !!!';
            gatewayUi = <Bacs gateway={gateway} updateSettings={updateSettings} getName={getName} {...attrs}/>;
            break;
        case 'paypal':
            gatewayUi = <PayPal gateway={gateway} updateSettings={updateSettings} getName={getName} {...attrs}/>
            break;
        case 'stripe':
            gatewayUi = <Stripe gateway={gateway} updateSettings={updateSettings} getName={getName} {...attrs}/>
            break;
    }

    return (
        <React.Fragment>
            <label className="label-header">
                <strong>{gateway.title}</strong>
                {getTooltip(tooltip)}
            </label>
            {gatewayUi}
        </React.Fragment>
    );
}