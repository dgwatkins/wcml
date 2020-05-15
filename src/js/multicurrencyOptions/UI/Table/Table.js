import React from "react";
import ColumnCurrencies from "./ColumnCurrencies";
import ColumnLanguages from "./ColumnLanguages";
import ColumnActions from "./ColumnActions";

const Table = () => {

    return (
        <React.Fragment>
            <ColumnCurrencies />
            <ColumnLanguages />
            <ColumnActions />
        </React.Fragment>
    )
}

export default Table;