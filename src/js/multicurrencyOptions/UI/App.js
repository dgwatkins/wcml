import React from "react";
import AddCurrency from "./AddCurrency";
import Table from "./Table";
import CurrencyModal from "./CurrencyModal";
import {useStore} from "../Store";
import ModeSelect from "./ModeSelect";

const App = () => {
    const [modalCurrency] = useStore('modalCurrency');

    return <div className="wcml-section-content wcml-section-content-wide">
        <div>
            <ModeSelect/>
            <br/>
            <div className="currencies-table-content">
                <div className="tablenav top clearfix">
                    <AddCurrency/>
                </div>
                <Table/>
                {modalCurrency && <CurrencyModal />}
            </div>
        </div>
    </div>
}

export default App;