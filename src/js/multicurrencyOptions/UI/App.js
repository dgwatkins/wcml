import React from "react";
import AddCurrency from "./AddCurrency";
import Table from "./Table/Table";
import CurrencyModal from "./CurrencyModal";
import {useStore} from "../Store";
import ModeSelect from "./ModeSelect"
import MaxMindSettings from "./MaxMindSettings"
import {getStoreProperty} from "../Store";

const App = () => {
    const [modalCurrency] = useStore('modalCurrency');
    const mode = getStoreProperty('mode');
    const languages = getStoreProperty('languages');
    const defaultByLocation = languages.filter( language => language.defaultCurrency == 'location' );

    if (!mode) {
        return <div>
                <ModeSelect/>
            </div>
    }

    return <div className="wcml-section-content wcml-section-content-wide">
        <div>
            <ModeSelect/>
            <br/>
            {('by_location' === mode || defaultByLocation.length > 0) && <MaxMindSettings/>}
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