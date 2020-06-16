import React from "react";
import Tooltip from "antd/lib/tooltip";

/**
 * Component for showing Select with label and tooltip
 *
 * @param onChange On change callback
 * @param {string} label
 * @param {array} attrs Attributes
 * @param {string} tooltip Tooltip text
 * @param {XML} children Options
 * @param {XML|null} afterSelect After Select
 * @returns {XML}
 * @constructor
 */
export const SelectRow = ({onChange, label, attrs, tooltip, children, afterSelect=null}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <select onChange={onChange} {...attrs}>
                {children}
            </select>
            {afterSelect}
        </div>
    );
};

/**
 * Component for showing Input with label and tooltip
 *
 * @param onChange On change callback
 * @param {string} label
 * @param {array} attrs Attributes
 * @param {string} tooltip Tooltip text
 * @returns {XML}
 * @constructor
 */
export const InputRow = ({onChange, label, attrs, tooltip}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <input {...attrs} onChange={onChange} />
        </div>
    );
};

/**
 * Component for showing tooltip text
 *
 * @param {string} tooltip Tooltip text
 * @returns {*|XML}
 */
export const getTooltip = tooltip => {
    return tooltip && <Tooltip title={allowBreakRules(tooltip)}> <i className="wcml-tip otgs-ico-help" /></Tooltip>;
};

const allowBreakRules = (string) => {
    return <div dangerouslySetInnerHTML={{__html:string}}/>;
};

/**
 * Component for showing Spinner
 *
 * @param style
 * @returns {XML}
 * @constructor
 */
export const Spinner = ({style}) => {
    const spinnerStyle = {
        visibility:'visible',
        ...style
    };

    return <span className="spinner" style={spinnerStyle} />;
};