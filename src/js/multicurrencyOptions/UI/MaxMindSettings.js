import React from "react";
import {useState} from "react";
import Input from 'antd/lib/input';
import {getStoreAction, getStoreProperty, useStore} from "../Store";
import strings from "../Strings";
import {Spinner} from "../../sharedComponents/FormElements";

const MaxMindSettings = () => {
    const keyExist = getStoreProperty('maxMindKeyExist');
    const status = getStoreProperty('maxMindRegistrationStatus');
    const registerMaxMindKey = getStoreAction('registerMaxMindKey');
    const [key, setKey] = useState('');
    const isFetching = 'fetching' === status;
    const isSuccess = 'success' === status;
    const error = status && ! isFetching && ! isSuccess;
    const show = isSuccess || error || !keyExist;

    const onChange = e => {
        setKey(e.target.value);
    };

    const onApply = e => {
        registerMaxMindKey(key);
    }

    return ( show &&
        <div className="max-mind-block">
            {isSuccess && <Success strings={strings}/>}
            <div className="max-mind-block__wrapper">
                {error && <Error error={status}/>}
                {!keyExist && <SettingsBlock onChange={onChange} onApply={onApply} strings={strings} disableSave={!key}/>}
                {isFetching && <Spinner/>}
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
