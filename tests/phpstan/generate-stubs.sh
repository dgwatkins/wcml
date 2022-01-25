./vendor/bin/generate-stubs \
  --force \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/otgs-ui.stub \
  \
  ../sitepress-multilingual-cms/vendor/otgs/ui/src/php \

./vendor/bin/generate-stubs \
  --force \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/page-builders.stub \
  \
  ../sitepress-multilingual-cms/addons/wpml-page-builders/classes \

./vendor/bin/generate-stubs \
  -vvv \
  --force \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/wpml-core.stub \
  \
  ../sitepress-multilingual-cms/inc/absolute-links \
  ../sitepress-multilingual-cms/inc/cache.php \
  ../sitepress-multilingual-cms/inc/constants.php \
  ../sitepress-multilingual-cms/inc/functions.php \
  ../sitepress-multilingual-cms/inc/functions-debug-information.php \
  ../sitepress-multilingual-cms/inc/functions-helpers.php \
  ../sitepress-multilingual-cms/inc/functions-load.php \
  ../sitepress-multilingual-cms/inc/functions-load-tm.php \
  ../sitepress-multilingual-cms/inc/functions-network.php \
  ../sitepress-multilingual-cms/inc/functions-sanitation.php \
  ../sitepress-multilingual-cms/inc/functions-security.php \
  ../sitepress-multilingual-cms/inc/functions-troubleshooting.php \
  ../sitepress-multilingual-cms/inc/icl-admin-notifier.php \
  ../sitepress-multilingual-cms/inc/import-xml.php \
  ../sitepress-multilingual-cms/inc/js-scripts.php \
  ../sitepress-multilingual-cms/inc/lang-data.php \
  ../sitepress-multilingual-cms/inc/language-switcher.php \
  ../sitepress-multilingual-cms/inc/not-compatible-plugins.php \
  ../sitepress-multilingual-cms/inc/plugin-integration-nextgen.php \
  ../sitepress-multilingual-cms/inc/plugins-integration.php \
  ../sitepress-multilingual-cms/inc/post-translation \
  ../sitepress-multilingual-cms/inc/query-filtering \
  ../sitepress-multilingual-cms/inc/setup \
  ../sitepress-multilingual-cms/inc/taxonomy-term-translation \
  ../sitepress-multilingual-cms/inc/template-functions.php \
  ../sitepress-multilingual-cms/inc/tools \
  ../sitepress-multilingual-cms/inc/translation-jobs \
  ../sitepress-multilingual-cms/inc/translation-management \
  ../sitepress-multilingual-cms/inc/upgrade.php \
  ../sitepress-multilingual-cms/inc/url-handling \
  ../sitepress-multilingual-cms/inc/utilities \
  ../sitepress-multilingual-cms/inc/wp-nav-menus \
  ../sitepress-multilingual-cms/inc/wpml-api.php \
  ../sitepress-multilingual-cms/inc/wpml-config \
  ../sitepress-multilingual-cms/inc/wpml-post-comments.class.php \
  ../sitepress-multilingual-cms/inc/wpml-post-edit-ajax.class.php \
  ../sitepress-multilingual-cms/inc/wpml-private-actions.php \
  ../sitepress-multilingual-cms/inc/wpml_load_request_handler.php \
  \
  ../sitepress-multilingual-cms/lib/http.php \
  ../sitepress-multilingual-cms/lib/icl_api.php \
  ../sitepress-multilingual-cms/lib/mobile-detect.php \
  ../sitepress-multilingual-cms/lib/select2 \
  \
  ../sitepress-multilingual-cms/compatibility \
  \
  ../sitepress-multilingual-cms/menu \
  ../sitepress-multilingual-cms/modules \
  ../sitepress-multilingual-cms/templates \
  ../sitepress-multilingual-cms/sitepress.php \
  \
  ../sitepress-multilingual-cms/classes \
  ../sitepress-multilingual-cms/classes/templating \
  ../sitepress-multilingual-cms/sitepress.class.php


./vendor/bin/generate-stubs  \
  --force  \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/wpml-vendor.stub \
  \
  ../sitepress-multilingual-cms/vendor/wpml/collect/src \
  ../sitepress-multilingual-cms/vendor/wpml/core-api/core \
  ../sitepress-multilingual-cms/vendor/wpml/fp/core \
  ../sitepress-multilingual-cms/vendor/wpml/wp/classes


#./vendor/bin/generate-stubs  \
#  --force  \
#  --functions \
#  --classes \
#  --interfaces \
#  --traits \
#  \
#  --out=tests/phpstan/stubs/wpml-tm.stub \
#  \
#  ../wpml-translation-management/classes \
#  ../wpml-translation-management/inc \
#  ../wpml-translation-management/menu \
#  ../wpml-translation-management/templates


./vendor/bin/generate-stubs  \
  --force  \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/wpml-media.stub \
  \
  ../wpml-media-translation/classes \
  ../wpml-media-translation/inc


./vendor/bin/generate-stubs  \
  --force  \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/wp-background-processing.stub \
  \
  ../wpml-translation-management/vendor/a5hleyrich/wp-background-processing


./vendor/bin/generate-stubs  \
  --force  \
  --functions \
  --classes \
  --interfaces \
  --traits \
  \
  --out=tests/phpstan/stubs/woocommerce.stub \
  \
  ../woocommerce/includes \
  ../woocommerce/packages/woocommerce-blocks/src \
  ../woocommerce/src \
  ../woocommerce/woocommerce.php
