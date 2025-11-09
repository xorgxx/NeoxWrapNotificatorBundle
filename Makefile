#---COMPOSER-#
COMPOSER = composer
COMPOSER_INSTALL = $(COMPOSER) install
COMPOSER_UPDATE = $(COMPOSER) update
#------------#
PHP="D:\SERVER-WEB\wamp64\bin\php\php8.3.10\php.exe"
PHPUNIT=$(PHP) vendor/bin/phpunit
PHPSTAN=$(PHP) -d memory_limit=$(MEMORY_LIMIT) vendor/bin/phpstan
CS_FIXER=$(PHP) vendor/bin/php-cs-fixer

MEMORY_LIMIT ?= 512M

install:
	$(COMPOSER) install --prefer-dist --no-progress --no-interaction

update:
	$(COMPOSER) update

cs-fix:
	$(CS_FIXER) fix

cs-check:
	$(CS_FIXER) fix --dry-run --diff --ansi

stan:
	$(PHPSTAN) analyse --no-progress --ansi

test:
	$(PHPUNIT) --colors=always

ci: cs-check stan test
