var WCML_Tooltip = {

    tiptip_args : {
        'attribute' : 'data-tip',
        'fadeIn' : 50,
        'fadeOut' : 50,
        'delay' : 200
    },

    init: function(){

        jQuery(document).ready(function () {
            WCML_Tooltip.load_tip();

        });
    },

    load_tip: function(){
        jQuery(".wcml-tip").tipTip( WCML_Tooltip.tiptip_args );
    },

    add_tip_before_elem: function ( elem_class, text, style ){
        jQuery( '<i class="otgs-ico-help wcml-tip" data-tip="'+text+'" style="'+style+'"></i>' ).insertBefore( elem_class );
        WCML_Tooltip.load_tip();
    },

    show: function(){
        jQuery( '.wcml-tip').show();
    },

    hide: function(){
        jQuery( '.wcml-tip').hide();
    },
}

WCML_Tooltip.init();