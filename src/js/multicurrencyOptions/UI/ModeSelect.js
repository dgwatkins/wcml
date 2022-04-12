import React from "react";
import {getStoreProperty, useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {allowBreakRules, Spinner} from "../../sharedComponents/FormElements";
import Radio from "antd/lib/radio";
import 'antd/lib/radio/style/index.css';
import {equals} from 'ramda';
import Tooltip from "antd/lib/tooltip";
import "../styles.scss";

const ModeSelect = () => {
    const [mode, setMode] = useStore('mode');
    const isStandalone = getStoreProperty('isStandalone');
    const ajax = createAjaxRequest('setCurrencyMode');
    const isMode = equals(mode);

    const onChange = async e => {
        const newMode = e.target.value;

        if (!isMode(newMode)) {
            await ajax.send(newMode);
            setMode(newMode);
        }
    };

    const wrapInByLanguageTooltip = radioOption => {
        return isStandalone
            ? <Tooltip id="wcml-by-language-tooltip" title={allowBreakRules(strings.labelSiteLanguageTooltip)}>{radioOption}</Tooltip>
            : radioOption;
    };

    return (
        <React.Fragment>
            {ajax.fetching && <Spinner/>}
            <p>{strings.labelModeSelect}</p>
            <div>
                <p>
                    {wrapInByLanguageTooltip(
                        <Radio name="currency_mode" value="by_language" disabled={isStandalone} onClick={onChange} checked={isMode("by_language")}> {strings.labelSiteLanguage}</Radio>
                    )}
                </p>
                <p>
                    <Radio name="currency_mode" value="by_location" onClick={onChange} checked={isMode("by_location")}> {strings.labelClientLocation}</Radio>
                </p>
            </div>
        </React.Fragment>
    );
};

export default ModeSelect;