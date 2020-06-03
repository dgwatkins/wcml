import React from "react";
import {useState} from "react";
import {Input} from 'antd';
import {useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";

const MaxMindSettings = () => {
    const [maxMindKeyExist, setMaxMindKeyExist] = useStore('maxMindKeyExist');
    const [maxMindKey, setMaxMindKey] = useState('');
    const [result, setResult] = useState(false);

    const onChange = e => {
        setMaxMindKey(e.target.value);
    };

    const ajax = createAjaxRequest('setMaxMindKey');

    const onApply = async e => {
        setResult( await ajax.send(maxMindKey) );

        if (result.data && result.data.success) {
            setMaxMindKeyExist();
        }
    };

    return (
        <div className="max-mind-block">
            {maxMindKeyExist && <OnSuccess strings={strings}/>}
            {(result.data && !result.data.success) && <OnError error={result.data.data}/>}
            {!maxMindKeyExist && <SettingsBlock onChange={onChange} onApply={onApply} strings={strings}/>}
        </div>
    );
};

const SettingsBlock = ({onChange, onApply, strings}) => {

    return (
        <React.Fragment>
            <span>{strings.maxMindDescription}</span>
            <br/>
            <label>{strings.maxMindLabel}</label>
            <Input.Password onChange={onChange} />
            <input type="button"
                   className="max-mind-apply"
                   onClick={onApply}
                   value={strings.apply}
            />
            <br/>
            <span>
                    {strings.maxMindDoc}
                <a className="wcml-max-min-doc"
                   href={strings.maxMindDocLink}
                   target="_blank"
                >
                        {strings.maxMindDocLinkText}
                    </a>
                </span>
        </React.Fragment>
    );
};


const OnSuccess = ({strings}) => {

    return (
        <div className="updated message fade">
            <p>
                {strings.maxMindSuccess}
                <a className="wcml-max-min-settings"
                   href={strings.maxMindSettingLink}
                   target="_blank"
                >
                    {strings.maxMindSettingLinkText}
                </a>
            </p>
        </div>
    );
};

const OnError = ({error}) => {

    return (
        <div id="message" className="error inline">
            <p className="wcml-max-min-error">{error}</p>
        </div>
    );
};

export default MaxMindSettings;