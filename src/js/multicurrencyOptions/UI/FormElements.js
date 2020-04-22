import React from "react";
import Tooltip from "antd/lib/tooltip";

export const SelectRow = ({onChange, label, tooltip, attrs, children}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <select onChange={onChange} {...attrs}>
                {children}
            </select>
        </div>
    );
};

export const InputRow = ({attrs, onChange, label, tooltip=null}) => {
    return (
        <div className="wpml-form-row">
            <label htmlFor={attrs.id}>{label}{getTooltip(tooltip)}</label>
            <input {...attrs} onChange={onChange} />
        </div>
    );
};

const getTooltip = (tooltip) => {
    return tooltip && <Tooltip title={tooltip}> <i className="wcml-tip otgs-ico-help" /></Tooltip>;
};