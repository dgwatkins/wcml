# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

.PHONY: precommit
.PHONY: dupes comp jest phpunit test dev prod
.PHONY: setup test

setup:: vendor/autoload.php
setup:: githooks

test:: jest
test:: phpunit

precommit:: validate-composer
precommit:: dupes
precommit:: compatibility


# precommit

dupes: vendor/autoload.php
	./.make/check-duplicates.sh

compatibility: vendor/autoload.php
	./.make/check-compatibility.sh

validate-composer: composer.lock
	./.make/check-composer.sh


# Dependency managers

## Composer

composer.lock: composer.json
	composer install
	touch $@

composer.json:
	composer init -q

vendor/autoload.php: composer.lock
	composer install
	touch $@


# Setup

githooks:
	find .git/hooks -type l -exec rm {} \;
	find .githooks -type f -exec ln -sf ../../{} .git/hooks/ \;


# Tests

phpunit: vendor/autoload.php
	vendor/bin/phpunit --fail-on-warning --configuration tests/phpunit/phpunit.xml
