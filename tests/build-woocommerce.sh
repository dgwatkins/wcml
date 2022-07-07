#!/usr/bin/env bash

cd /tmp/woocommerce/plugins/woocommerce
composer -q install --no-dev
php bin/generate-feature-config.php
