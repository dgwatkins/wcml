import React from "react";
import AddCurrency from "./AddCurrency";
import Table from "./Table";
import {useStoreState} from "easy-peasy";

const App = () => {
    const currencies = useStoreState(state => state.currencies);
    const languages  = useStoreState(state => state.languages);

    return <div className="wcml-section-content wcml-section-content-wide">
        <div>
            <div className="currencies-table-content">
                <div className="tablenav top clearfix">
                    <AddCurrency/>
                </div>
                <Table currencies={currencies} languages={languages}/>
            </div>
        </div>
    </div>
}

export default App;