PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install:
	composer install

validate:
	composer validate

test:
	composer exec --verbose phpunit tests -- --display-notices --testdox

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover=build/logs/clover.xml

show-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit -- --coverage-text

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src

phpstan:
	composer exec --verbose phpstan analyse -- -c phpstan.neon src