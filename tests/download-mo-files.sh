#!/usr/bin/env bash

WC_VERSION_FOR_MO_FILES='2.6.4'

# ${OTGS_DEPENDENCY_PATH} is defined by the OTGS-CI script

WCML_PLUGIN_PATH=${OTGS_CI_DEPENDENCY_PATH}

WP_TESTS_DIR=${WCML_PLUGIN_PATH}/tests/wordpress-tests-lib/
WP_TESTS_LANG_PLUGINS_DIR=${WP_TESTS_DIR}/data/languages/plugins


# Create languages directory
mkdir -p ${WP_TESTS_LANG_PLUGINS_DIR}

#WC lang packs
wget -P ${WP_TESTS_LANG_PLUGINS_DIR} https://downloads.wordpress.org/translation/plugin/woocommerce/$WC_VERSION_FOR_MO_FILES/fr_FR.zip
wget -P ${WP_TESTS_LANG_PLUGINS_DIR} https://downloads.wordpress.org/translation/plugin/woocommerce/$WC_VERSION_FOR_MO_FILES/de_DE.zip
wget -P ${WP_TESTS_LANG_PLUGINS_DIR} https://downloads.wordpress.org/translation/plugin/woocommerce/$WC_VERSION_FOR_MO_FILES/ru_RU.zip
wget -P ${WP_TESTS_LANG_PLUGINS_DIR} https://downloads.wordpress.org/translation/plugin/woocommerce/$WC_VERSION_FOR_MO_FILES/es_ES.zip

cd ${WP_TESTS_LANG_PLUGINS_DIR}
unzip -o fr_FR.zip
unzip -o de_DE.zip
unzip -o ru_RU.zip
unzip -o es_ES.zip

rm fr_FR.zip
rm de_DE.zip
rm ru_RU.zip
rm es_ES.zip
