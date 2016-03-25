(function () {

    var attributeSelector = jQuery('#wcml_product_attributes');


    jQuery(document).ready(function () {

        attributeSelector.on('change', switchAttribute);

    });

    function switchAttribute(){
        "use strict";
        var attributeName = jQuery(this).val();

        var wrap        = jQuery('#taxonomy-translation');
        var spinner     = '<div class="loading-content"><span class="spinner loading-content" style="visibility: visible;"></span></div>';

        wrap.html(spinner);

        updateAttributeInfo( attributeName );

        TaxonomyTranslation.classes.taxonomy = new TaxonomyTranslation.models.Taxonomy({taxonomy: attributeName});
        TaxonomyTranslation.mainView = new TaxonomyTranslation.views.TaxonomyView({model: TaxonomyTranslation.classes.taxonomy}, {sync: isSyncTab()});


    }

    function isSyncTab(){
        "use strict";

        return  window.location.search.substring(1).indexOf('&sync=1') > -1;
    }

    function updateAttributeInfo( taxonomy ){
        jQuery('.wrap .icl_tt_main_bottom').remove();

        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "wcml_update_term_translated_warnings",
                taxonomy: taxonomy,
                show_sync: true
            },
            success: function (response) {
                jQuery('.tax-product-attributes').removeAttr('title');
                jQuery('.tax-product-attributes i.otgs-ico-warning').remove();
                if ( !response.hide ) {
                    jQuery('.tax-product-attributes').attr('title', jQuery('#warn_title').val() );
                    jQuery('.tax-product-attributes').append('<i class="otgs-ico-warning"></i>');
                }

                if( response.bottom_html ){
                    jQuery('.wcml-wrap .wrap').append( '<div class="icl_tt_main_bottom">'+response.bottom_html+'</div>' );
                }
            }
        });

    }



})();
