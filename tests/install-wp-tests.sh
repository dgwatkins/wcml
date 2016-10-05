#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

SCRIPTPATH=`pwd -P`

WP_TESTS_DIR=${SCRIPTPATH}/wordpress-tests-lib/
WP_CORE_DIR=${SCRIPTPATH}/wordpress/
WP_CORE_LANG_DIR=${SCRIPTPATH}/wordpress/wp-content/languages/
WP_CORE_LANG_PLUGINS_DIR=${SCRIPTPATH}/wordpress/wp-content/languages/plugins/

set -ex

install_wp() {
	mkdir -p $WP_CORE_DIR

	local WC_VERSION_FOR_MO_FILES='2.6.4'

	if [ $WP_VERSION == 'latest' ]; then 
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	wget -nv -O /tmp/wordpress.tar.gz http://wordpress.org/${ARCHIVE_NAME}.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	

	# Create languages directory
  	mkdir -p $WP_CORE_LANG_DIR
  	mkdir -p $WP_CORE_LANG_PLUGINS_DIR
  	#WC lang packs
  	wget -P $WP_CORE_LANG_PLUGINS_DIR https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION_FOR_MO_FILES}/fr_FR.zip
  	wget -P $WP_CORE_LANG_PLUGINS_DIR https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION_FOR_MO_FILES}/de_DE.zip
  	wget -P $WP_CORE_LANG_PLUGINS_DIR https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION_FOR_MO_FILES}/ru_RU.zip
  	wget -P $WP_CORE_LANG_PLUGINS_DIR https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION_FOR_MO_FILES}/es_ES.zip
  	wget -P $WP_CORE_LANG_PLUGINS_DIR https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION_FOR_MO_FILES}/it_IT.zip

  	cd $WP_CORE_LANG_PLUGINS_DIR
  	unzip fr_FR.zip
  	unzip de_DE.zip
  	unzip ru_RU.zip
  	unzip es_ES.zip

	wget -nv -O $WP_CORE_DIR/wp-content/db.php https://raw.github.com/markoheijnen/wp-mysqli/master/db.php
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR
	
	if [ $WP_VERSION == 'latest' ]; then
	    svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/
    else
        svn co --quiet http://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/includes/
    fi

	sed $ioption "s:DIR_TESTDATA . '/languages': '$WP_CORE_LANG_DIR':" includes/bootstrap.php

	wget -nv -O wp-tests-config.php http://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_test_suite
install_db
