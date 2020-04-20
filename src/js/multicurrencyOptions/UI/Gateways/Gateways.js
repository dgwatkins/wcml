import React, {useState} from "react";
import {useStore} from "../../Store";

const Gateways = () => {
    const [currency, setModalCurrency] = useStore('modalCurrency');

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
                            Object.keys(currency.gatewaySettings).map((gatewayId) => {
                                return (
                                    <React.Fragment key={gatewayId}>
                                        <label className="label-header"><strong>{gatewayId}</strong> <i
                                            className="wcml-tip otgs-ico-help"></i></label>
                                        <div className="wpml-form-row">
                                            Some settings...
                                        </div>
                                    </React.Fragment>
                                );
                            })
                        }
                    </div>
                )
            }
        </div>
    );
};

export default Gateways;