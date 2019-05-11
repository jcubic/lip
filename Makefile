
.PHONY: test coveralls coverage

test:
	php ./vendor/bin/phpunit

coveralls:
	php vendor/bin/php-coveralls -v
