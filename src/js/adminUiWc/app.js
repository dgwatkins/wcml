/* global document, wp */

document.addEventListener('DOMContentLoaded', () => {
    wp.hooks.addFilter(
        'woocommerce_admin_notices_to_show',
        'WCML/whitelistAllOTGSNotices',
        (noticesToShow) => [
                ...noticesToShow,
                /**
                 * @see client/header/activity-panel/wordpress-notices.js
                 *
                 * [ element id, [ classes to include ], [ classes to exclude ] ]
                 */
                [ null, [ 'otgs-notice' ] ],
        ],
        10
    );
});
