import React from "react";
import {useState} from "react";
import {Input} from 'antd';
import {useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {Spinner} from "../../sharedComponents/FormElements";

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
            <div className="max-mind-block__wrapper">
                {(result.data && !result.data.success) && <OnError error={result.data.data}/>}
                {!maxMindKeyExist && <SettingsBlock onChange={onChange} onApply={onApply} strings={strings}/>}
                {ajax.fetching && <Spinner/>}
            </div>
        </div>
    );
};

const SettingsBlock = ({onChange, onApply, strings}) => {

    return (
        <React.Fragment>
            <p>{strings.maxMindDescription}</p>
            <div className="max-mind-block__wrapper-form">
                <label>{strings.maxMindLabel}</label>
                <div className="max-mind-block__wrapper-form-input">
                    <Input.Password onChange={onChange} />
                    <input type="button"
                           className="max-mind-apply button-primary"
                           onClick={onApply}
                           value={strings.apply}
                    />
                </div>
            </div>

            <p className="max-mind-block__wrapper-generate">
                    {strings.maxMindDoc}
                <a className="wcml-max-min-doc wpml-external-link"
                   href={strings.maxMindDocLink}
                   target="_blank"
                >
                        {strings.maxMindDocLinkText}
                    </a>
                </p>
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
        <div className="error inline">
            <p className="wcml-max-min-error">{error}</p>
        </div>
    );
};

export default MaxMindSettings;
