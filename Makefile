# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

# Help
.PHONY: help

help:
	$(info Run `make setup` to configure the Git Hooks and install the dependencies`)
	$(info Run `make install` to install the dependencies)
	$(info Run `make install-prod` to install the dependencies in production mode)
#	$(info - Run `make composer-install` to only install Composer dependencies)
#	$(info - Run `make composer-install-prod` to only install Composer dependencies in production mode)
#	$(info - Run `make yarn-install` to only install Yarn/Node dependencies)
#	$(info - Run `make yarn-install-prod` to only install Yarn/Node dependencies in production mode)
	$(info Run `make tests` to run all tests)
#	$(info - Run `make jest` to run only Jest tests)
#	$(info - Run `make phpunit` to run only PhpUnit tests)
#	$(info Run `make dev` to bundle WebPack modules in development mode)
#	$(info Run `make prod` to bundle WebPack modules in production mode)

# Setup
.PHONY: setup githooks

setup:: githooks
setup:: install

githooks:
ifdef CI
	$(info Skipping Git Hooks in CI)
else ifdef OS
	cp .githooks/* .git/hooks/
	$(info Looks like you are on Windows... files copied.)

else
	@find .git/hooks -type l -exec rm {} \;
	@find .githooks -type f -exec ln -sf ../../{} .git/hooks/ \;
	$(info Git Hooks installed)
endif

# Install
.PHONY: install

install: composer-install
#install: yarn-install

install-prod: composer-install-prod
#install-prod: yarn-install-prod

# Build
.PHONY: dev prod

#dev prod: yarn-install
#	@yarn run build:$@
#	$(info WebPack modules bundled)

# Tests
.PHONY: tests

#tests:: jest
tests:: phpunit

# Git Hooks
.PHONY: precommit

precommit:: validate-composer
#precommit:: validate-yarn
precommit:: dupes
precommit:: compatibility

# precommit
.PHONY: dupes compatibility validate-composer validate-yarn

dupes: composer-install
	./.make/check-duplicates.sh

compatibility: composer-install
	./.make/check-compatibility.sh

validate-composer: composer-install
	./.make/check-composer.sh

#validate-yarn: yarn-install
#	./.make/check-yarn.sh


# Dependency managers

## Composer
.PHONY: composer-install

composer.lock: composer-install
	@touch $@

vendor/autoload.php: composer-install
	@touch $@

composer-install:
	$(info Installing Composer dependencies)
	@composer install

composer-install-prod:
	$(info Installing Composer dependencies)
	@composer --no-dev install

## Yarn
.PHONY: yarn-install

#package.json: yarn-install
#	@touch $@
#
#yarn.lock: yarn-install
#	@touch $@
#
#yarn-install:
#	$(info Installing Yarn/Node dependencies)
#	@yarn install
#
#yarn-install-prod:
#	$(info Installing Yarn/Node dependencies)
#	@yarn --prod install

# Tests
.PHONY: jest phpunit

#jest: yarn-install
#	$(info Running Jest)
#	@yarn run test

phpunit: composer-install
	$(info Running PhpUnit)
	@vendor/bin/phpunit --fail-on-warning --configuration tests/phpunit/phpunit.xml
