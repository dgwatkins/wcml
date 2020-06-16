import React from "react";
import {useStore, getStoreProperty} from "../../Store";
import {assocPath} from "ramda";
import Bacs from "./Bacs";
import PayPal from "./PayPal";
import Stripe from "./Stripe";
import Unsupported from "./Unsupported";
import {getTooltip} from "../../../sharedComponents/FormElements";
import strings from "../../Strings";

const Gateways = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');
    const gateways = getStoreProperty('gateways');
    const activeCurrencies = getStoreProperty('activeCurrencies');

    const getActiveCurrenciesAndModal = () => {
        return currency.isNew ? [currency, ...activeCurrencies] : activeCurrencies;
    };

    return (
        <div>
            <label className="label-header"><strong>{strings.labelPaymentGateways}</strong></label>

            <label className="wcml-gateways-switcher">
                <input name="currency_options[gateways_enabled]" type="checkbox"
                       className="wcml-gateways-enabled otgs-switcher-input"
                       checked={currency.gatewaysEnabled}
                       onChange={() => setModalCurrency({...currency, ['gatewaysEnabled']: !currency.gatewaysEnabled})}/>
                <span className="otgs-switcher wpml-theme" data-on="ON" data-off="OFF"/>
                <a className="wpml-external-link"
                   href={strings.linkUrlLearn}
                   target="_blank"
                >
                    {strings.linkLabelLearn}
                </a>
            </label>

            {
                currency.gatewaysEnabled && (
                    <div className='wcml-gateways'>
                        {
                            gateways.map((gateway, key) => {
                                return <Gateway key={key}
                                                gateway={gateway}
                                                settings={currency.gatewaysSettings[gateway.id] || {}}
                                                activeCurrencies={getActiveCurrenciesAndModal()}
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
    const updateSettings = newSettings => {
        setModalCurrency(
            assocPath(
                ['gatewaysSettings', gateway.id],
                {
                    ...currency.gatewaysSettings[gateway.id],
                    ...newSettings
                },
                currency
            )
        );
    };

    const getName = name => 'currency_options[gateways_settings][' + gateway.id + '][' + name + ']'

    let gatewayUi = ! currency.isDefault && <Unsupported gateway={gateway}/>;

    switch (gateway.id) {
        case 'bacs':
            gatewayUi = <Bacs gateway={gateway} updateSettings={updateSettings} getName={getName} currency={currency} {...attrs}/>;
            break;
        case 'paypal':
            gatewayUi = ! currency.isDefault && <PayPal gateway={gateway} updateSettings={updateSettings} getName={getName} currency={currency} {...attrs}/>
            break;
        case 'stripe':
            gatewayUi = ! currency.isDefault && <Stripe gateway={gateway} updateSettings={updateSettings} getName={getName} currency={currency} {...attrs}/>
            break;
    }

    return gatewayUi && (
        <React.Fragment>
            <label className="label-header">
                <strong>{gateway.title}</strong>
                {getTooltip(gateway.tooltip)}
            </label>
            {gatewayUi}
        </React.Fragment>
    );
}