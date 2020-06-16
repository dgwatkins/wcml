import React from "react";
import {createAjaxRequest} from "../../Request";
import {useStore, getStoreAction, getStoreProperty} from "../../Store";
import strings from "../../Strings";
import {Spinner} from "../FormElements";

const ColumnActions = () => {
    const activeCurrencies = getStoreProperty('activeCurrencies');
    const mode = getStoreProperty('mode');

    return <table className="widefat currency_settings_table" id="currency-settings-table">
                <thead>
                    <tr>
                        <th colSpan="2">{strings.labelSettings}</th>
                    </tr>
                </thead>
                <tbody>

                    {activeCurrencies.map(currency => <Row key={currency.code} currency={currency} />)}

                    {'by_language' === mode && <tr className="default_currency">
                        <td colSpan="2"/>
                    </tr>
                    }
                </tbody>
            </table>
};

export default ColumnActions;

const Row = ({currency}) => {
    const dataKey = 'wcml_currency_options_' + currency.code;
    const [ , setModalCurrency] = useStore('modalCurrency');

    const onClickEdit = (event) => {
        event.preventDefault();
        setModalCurrency(currency);
    };

    return <tr id={'wcml-row-currency-actions-' + currency.code } className="wcml-row-currencies-actions">
                <td className="wcml-col-edit">
                    <a href="#" title={strings.labelEdit}
                       className="edit_currency"
                       data-currency={currency.code}
                       data-content={dataKey}
                       data-dialog={dataKey}
                       data-height="530" data-width="480"
                       onClick={onClickEdit}
                    >
                        <i className="otgs-ico-edit" title={strings.labelEdit} />
                    </a>
                </td>
                <td className="wcml-col-delete">
                    <DeleteCell currency={currency} />
                </td>
            </tr>
};

const DeleteCell = ({currency}) => {
    const deleteCurrency = getStoreAction('deleteCurrency');
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
            <a title={strings.labelDelete} className="delete_currency"
               data-currency_name={currency.label}
               data-currency_symbol={currency.symbol}
               data-currency={currency.code} href="#"
               onClick={onClick}
            >
                {
                    ajax.fetching ?
                        (
                            <Spinner style={{margin: 0}}/>
                        ) : (
                            <i className="otgs-ico-delete" title={strings.labelDelete}/>
                        )
                }

            </a>
        );
};
