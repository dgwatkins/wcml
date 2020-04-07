import React from "react";
import AddCurrency from "./AddCurrency";
import TableFragmentLeft from "./TableFragmentLeft";
import TableFragmentCenter from "./TableFragmentCenter";
import TableFragmentRight from "./TableFragmentRight";

const Layout = ({currencies, languages}) => {
    return <div className="wcml-section-content wcml-section-content-wide">
                <div>
                    <div className="currencies-table-content">
                        <div className="tablenav top clearfix">
                            <AddCurrency/>
                        </div>
                        <TableFragmentLeft currencies={currencies} />
                        <TableFragmentCenter currencies={currencies} languages={languages} />
                        <TableFragmentRight currencies={currencies} />
                    </div>
                </div>
            </div>
}

export default Layout;