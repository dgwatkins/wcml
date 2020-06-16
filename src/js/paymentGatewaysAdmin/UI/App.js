import React from "react";
import {useState} from "react";
import strings from "../Strings";
import {CountriesFilter} from "../../sharedComponents/CountriesFilter";

const App = ({gatewayId, allCountries, initialSettings}) => {

    const [settings, setSettings] = useState(initialSettings);

    const updateSettings = (prop) => (value) => {
        setSettings({...settings, [prop]:value});
    };

    return (
        <div>
            <input type="hidden" name="wcml_payment_gateways[ID]" value={gatewayId}/>
            <CountriesFilter modeAttrs={getRowAttrs(settings, 'mode')}
                             currentMode={settings.mode}
                             onChangeMode={updateSettings('mode')}
                             selectCountriesAttrs={getRowAttrs(settings, 'countries')}
                             onChangeSelectedCountries={updateSettings('countries')}
                             allCountries={allCountries}
                             strings={strings}/>
        </div>
    );
};

const getRowAttrs = (settings, prop, attrs={}) => {
    return {
        id: "wcml_payment_gateways_" + prop,
        name: "wcml_payment_gateways[" + prop + "]",
        className: "payment_gateways_" + prop,
        value: settings[prop],
        ...attrs
    };
};

export default App;