import React from "react";
import Tooltip from "antd/lib/tooltip";

export const SelectRow = ({onChange, label, attrs, tooltip, children}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <select onChange={onChange} {...attrs}>
                {children}
            </select>
        </div>
    );
};

export const InputRow = ({onChange, label, attrs, tooltip}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <input {...attrs} onChange={onChange} />
        </div>
    );
};

export const getTooltip = tooltip => {
    return tooltip && <Tooltip title={tooltip}> <i className="wcml-tip otgs-ico-help" /></Tooltip>;
};