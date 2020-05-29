import React from "react";
import ColumnCurrencies from "./ColumnCurrencies";
import ColumnLanguages from "./ColumnLanguages";
import ColumnActions from "./ColumnActions";
import ColumnCountries from "./ColumnCountries"
import {getStoreProperty} from "../../Store";

const Table = () => {

    const mode = getStoreProperty('mode');

    return (
        <React.Fragment>
            <ColumnCurrencies />
            {'by_language' === mode ? <ColumnLanguages /> : <ColumnCountries />}
            <ColumnActions />
        </React.Fragment>
    )
}

export default Table;