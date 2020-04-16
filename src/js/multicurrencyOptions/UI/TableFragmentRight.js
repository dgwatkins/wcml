import React from "react";
import {useStoreState, useStoreActions} from "easy-peasy";
import {createAjaxRequest} from "../Request";
import {useStore} from "../Store";
import CurrencyModal from "./CurrencyModal";

const TableFragmentRight = () => {
    const activeCurrencies = useStoreState(state => state.activeCurrencies);

    return <table className="widefat currency_settings_table" id="currency-settings-table">
                <thead>
                    <tr>
                        <th colSpan="2">Settings</th>
                    </tr>
                </thead>
                <tbody>

                    {activeCurrencies.map(currency => <Row key={currency.code} currency={currency} />)}

                    <tr className="default_currency">
                        <td colSpan="2"></td>
                    </tr>
                </tbody>
            </table>
};

export default TableFragmentRight;

const Row = ({currency}) => {
    const titleEdit = 'Edit';
    const dataKey = 'wcml_currency_options_' + currency.code;
    const modalCurrencyCode = useStoreState(state => state.modalCurrencyCode);
    const setModalCurrencyCode = useStoreActions(action => action.setModalCurrencyCode);

    const onClickEdit = (event) => {
        event.preventDefault();
        setModalCurrencyCode(currency.code);
    };

    const showModal = modalCurrencyCode === currency.code
        && ( <CurrencyModal currency={currency} /> );

    return <tr id={'wcml-row-currency-actions-' + currency.code } className="wcml-row-currencies-actions">
                <td className="wcml-col-edit">
                    <a href="#" title={titleEdit}
                       className="edit_currency"
                       data-currency={currency.code} data-content={dataKey}
                       data-dialog={dataKey}
                       data-height="530" data-width="480"
                       onClick={onClickEdit}
                    >
                        <i className="otgs-ico-edit" title={titleEdit} />
                    </a>
                </td>
                <DeleteCell currency={currency} />
                {showModal}
            </tr>
};

const DeleteCell = ({currency}) => {
    const titleDelete = 'Delete';
    const deleteCurrency = useStoreActions(action => action.deleteCurrency);
    const ajax = createAjaxRequest('deleteCurrency');
    const [updating, setUpdating] = useStore('updating');

    const onClick = async (event) => {
        event.preventDefault();

        if (updating) {
            return;
        }

        setUpdating(true);
        const result = await ajax.send({currencyCode: currency.code});

        if (result.data && result.data.success) {
            deleteCurrency(currency.code);
        }

        setUpdating(false);
    };

    return ! currency.isDefault
        && (
            <td className="wcml-col-delete">
                <a title={titleDelete} className="delete_currency"
                   data-currency_name={currency.label}
                   data-currency_symbol={currency.symbol}
                   data-currency={currency.code} href="#"
                   onClick={onClick}
                >
                    <i className={ajax.fetching ? "spinner" : "otgs-ico-delete"}
                       style={ajax.fetching ? {visibility: "visible", margin: 0} : {}}
                       title={titleDelete}
                    />
                </a>
            </td>
        );
};