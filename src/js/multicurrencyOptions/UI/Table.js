import React from "react";
import TableFragmentLeft from "./TableFragmentLeft";
import TableFragmentCenter from "./ByLanguage/TableFragmentCenter";
import TableFragmentRight from "./TableFragmentRight";

const Table = ({currencies, languages}) => {

    return (
        <React.Fragment>
            <TableFragmentLeft currencies={currencies} />
            <TableFragmentCenter currencies={currencies} languages={languages} />
            <TableFragmentRight currencies={currencies} />
        </React.Fragment>
    )
}

export default Table;