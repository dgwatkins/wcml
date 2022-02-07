import React from "react";
import {useState, useRef} from "react";
import Input from 'antd/lib/input';
import {getStoreAction, getStoreProperty, useStore} from "../Store";
import strings from "../Strings";
import {Spinner} from "../../sharedComponents/FormElements";

const MaxMindSettings = () => {
    const isRegistered = getStoreProperty('isMaxMindRegistered');
    const showSettings = useRef( ! isRegistered ).current;
    const registerMaxMindKey = getStoreAction('registerMaxMindKey');
    const [key, setKey] = useState('');
    const isValidating = getStoreProperty('isValidatingMaxMindRegistration');
    const error = getStoreProperty('errorOnMaxMindRegistration');

    const onChange = e => setKey(e.target.value);

    const onApply = () => registerMaxMindKey(key);

    return ( showSettings &&
        <div className="max-mind-block">
            <Success strings={strings}/>
            <div className="max-mind-block__wrapper">
                <Error error={error}/>
                {!isRegistered && <SettingsBlock onChange={onChange} onApply={onApply} strings={strings} disableSave={!key}/>}
                {isValidating && <Spinner/>}
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

    return getStoreProperty( 'isMaxMindRegistered' )
        ? <div className="updated message fade">
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
        : null;
};

const Error = () => {
    const error = getStoreProperty('errorOnMaxMindRegistration');

    return error
        ? <div className="error inline">
            <p className="wcml-max-min-error">{error}</p>
        </div>
        : null;
};

export default MaxMindSettings;
