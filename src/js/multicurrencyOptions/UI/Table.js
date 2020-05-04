import React from "react";
import TableFragmentLeft from "./TableFragmentLeft";
import TableFragmentCenter from "./ByLanguage/TableFragmentCenter";
import TableFragmentRight from "./TableFragmentRight";
import {getStoreProperty} from "../Store";

const Table = () => {
    const mode = getStoreProperty('mode');

    return (
        <React.Fragment>
            <TableFragmentLeft />
            {'by_country' === mode ? <CenterByCountry/> : <TableFragmentCenter />}
            <TableFragmentRight />
        </React.Fragment>
    )
}

export default Table;

const CenterByCountry = () => {
    return (
        <div className="currency_wrap">
            <div className="currency_inner">
                This is a fragment for Country settings!
            </div>
        </div>
    );
}