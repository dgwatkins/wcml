import React from "react";
import {useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {Spinner} from "../../sharedComponents/FormElements";

const ModeSelect = () => {
    const [mode, setMode] = useStore('mode');
    const ajax = createAjaxRequest('setCurrencyMode');

    const onChange = async e => {
        const newMode = e.target.value;
        await ajax.send(newMode);
        setMode(newMode);
    };

    return (
        <React.Fragment>
            {ajax.fetching && <Spinner/>}
            <label>{strings.labelModeSelect}</label>
            <select id="currency_mode" value={mode} onChange={onChange}>
                {!mode && <option value="">{strings.labelChooseOption}</option>}
                <option value="by_language">{strings.labelSiteLanguage}</option>
                <option value="by_location">{strings.labelClientLocation}</option>
            </select>
        </React.Fragment>
    );
};

export default ModeSelect;