import React from "react";
import AddCurrency from "./AddCurrency";
import TableFragmentLeft from "./TableFragmentLeft";

const Layout = ({currencies}) => {
    return <div className="wcml-section-content wcml-section-content-wide">
                <div>
                    <div className="currencies-table-content">
                        <div className="tablenav top clearfix">
                            <AddCurrency/>
                        </div>
                        <TableFragmentLeft currencies={currencies} />
                    </div>
                </div>
            </div>
}

export default Layout;