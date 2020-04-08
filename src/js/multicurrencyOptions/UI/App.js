import React from "react";
import AddCurrency from "./AddCurrency";
import Table from "./Table";

const App = () => {

    return <div className="wcml-section-content wcml-section-content-wide">
        <div>
            <div className="currencies-table-content">
                <div className="tablenav top clearfix">
                    <AddCurrency/>
                </div>
                <Table/>
            </div>
        </div>
    </div>
}

export default App;