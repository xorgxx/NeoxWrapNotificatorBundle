COMPOSER=composer
PHPUNIT=vendor/bin/phpunit
PHPSTAN=vendor/bin/phpstan
CS_FIXER=vendor/bin/php-cs-fixer

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
