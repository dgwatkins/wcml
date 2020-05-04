import React from "react";
import TableFragmentLeft from "./TableFragmentLeft";
import TableFragmentCenter from "./ByLanguage/TableFragmentCenter";
import TableFragmentRight from "./TableFragmentRight";

const Table = () => {

    return (
        <React.Fragment>
            <TableFragmentLeft />
            <TableFragmentCenter />
            <TableFragmentRight />
        </React.Fragment>
    )
}

export default Table;