import React from "react";
import AddCurrency from "./AddCurrency";
import Table from "./Table/Table";
import CurrencyModal from "./CurrencyModal";
import {useStore} from "../Store";
import ModeSelect from "./ModeSelect"
import MaxMindSettings from "./MaxMindSettings"
import {getStoreProperty} from "../Store";

const App = () => {

    const mode = getStoreProperty('mode');

    return <div>
            <ModeSelect/>
            <br/>
            {mode && <CurrenciesSettings mode={mode}/>}
        </div>
};

const CurrenciesSettings = ({mode}) => {
    const [modalCurrency] = useStore('modalCurrency');
    const languages = getStoreProperty('languages');
    const defaultByLocation = languages.filter(language => language.defaultCurrency == 'location');
    const needsGeoLocation = 'by_location' === mode || defaultByLocation.length > 0;

    return (
        <React.Fragment>
            {needsGeoLocation && <MaxMindSettings/>}
            <br/>
            <div className="currencies-table-content">
                <div className="tablenav top clearfix">
                    <AddCurrency/>
                </div>
                <Table/>
                {modalCurrency && <CurrencyModal/>}
            </div>
        </React.Fragment>
    );
};

export default App;
