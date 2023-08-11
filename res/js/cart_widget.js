document.addEventListener('DOMContentLoaded', function() {
	document.addEventListener('click', function(e) {
        if (e.target.matches('.wcml_removed_cart_items_clear')) {
            e.preventDefault();
            wcml_cart_clear_removed_items();
        }
    });

    var getCookieValue = function(name) {
        return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop() || '';
    }

    // Check sessionStorage as well as cookies, for backward compatibility.
    var empty_cart_hash = ! sessionStorage.getItem('woocommerce_cart_hash')
        && ! getCookieValue('woocommerce_cart_hash');
    if (empty_cart_hash || actions.is_lang_switched == 1 || actions.force_reset == 1) {
        wcml_reset_cart_fragments();
    }
});

function wcml_reset_cart_fragments() {
    try {
        document.body.dispatchEvent(new Event('wc_fragment_refresh'));
        sessionStorage.removeItem('wc_fragments');
    } catch (err) { }
}

function wcml_cart_clear_removed_items() {
    var xhr = new XMLHttpRequest();
    var formData = new FormData();

    formData.append('action', 'wcml_cart_clear_removed_items');
    formData.append('wcml_nonce', document.querySelector('#wcml_clear_removed_items_nonce').value);

    xhr.open('POST', woocommerce_params.ajax_url);
    xhr.onload = function() {
        if (xhr.status === 200) {
            window.location = window.location.href;
        }
    };
    xhr.send(formData);
}
