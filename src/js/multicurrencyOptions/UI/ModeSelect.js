import React from "react";
import {useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import {Spinner} from "./FormElements";

const ModeSelect = () => {
    const [mode, setMode] = useStore('mode');
    const ajax = createAjaxRequest('setCurrencyMode');

    const onChange = async e => {
        const newMode = e.target.value;
        console.log(newMode);
        setMode(newMode);
        const result = await ajax.send(newMode);

        if (!result) {
            setMode(mode);
        }
    };

    return (
        <React.Fragment>
            <select value={mode} onChange={onChange}>
              <option value='by_language'>By Language</option>
              <option value='by_country'>By Country</option>
            </select>
            {ajax.fetching && <Spinner/>}
        </React.Fragment>
    );
};

export default ModeSelect;