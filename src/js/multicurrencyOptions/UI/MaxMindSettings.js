import React from "react";
import {useState} from "react";
import Input from 'antd/lib/input';
import {useStore} from "../Store";
import {createAjaxRequest} from "../Request";
import strings from "../Strings";
import {Spinner} from "../../sharedComponents/FormElements";
import {path} from 'ramda';

const MaxMindSettings = () => {
    const [keyExist, setKeyExist] = useStore('maxMindKeyExist');
    const [key, setKey] = useState('');
    const [isSuccess, setIsSuccess] = useState(null);
    const [errorMsg, setErrorMsg] = useState(null);
    const show = isSuccess || errorMsg || ! keyExist;

    const onChange = e => {
        setKey(e.target.value);
    };

    const ajax = createAjaxRequest('setMaxMindKey');

    const onApply = async e => {
        const response    = await ajax.send(key);
        const isSuccess   = path(['data', 'success']);
        const getErrorMsg = path(['data', 'data']);

        if (isSuccess(response)) {
            setIsSuccess(true);
            setKeyExist(true);
        } else if (getErrorMsg(response)) {
            setErrorMsg(getErrorMsg(response));
        }
    };

    return ( show &&
        <div className="max-mind-block">
            {isSuccess && <Success strings={strings}/>}
            <div className="max-mind-block__wrapper">
                {errorMsg && <Error error={errorMsg}/>}
                {!keyExist && <SettingsBlock onChange={onChange} onApply={onApply} strings={strings} disableSave={!key}/>}
                {ajax.fetching && <Spinner/>}
            </div>
        </div>
    );
};

const SettingsBlock = ({onChange, onApply, strings, disableSave}) => {

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
                           disabled={disableSave}
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


const Success = ({strings}) => {

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

const Error = ({error}) => {

    return (
        <div className="error inline">
            <p className="wcml-max-min-error">{error}</p>
        </div>
    );
};

export default MaxMindSettings;
