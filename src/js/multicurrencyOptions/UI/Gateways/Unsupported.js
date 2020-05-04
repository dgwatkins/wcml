import React from "react";

const Unsupported = ({gateway}) => {
    return (
        <div className="wpml-form-row">
            <span className="explanation-text">{gateway.strings.labelNotYetSupported}</span>
        </div>
    )
}

export default Unsupported;