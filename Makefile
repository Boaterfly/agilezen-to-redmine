all: vendor

vendor: composer.lock
	composer install --optimize-autoloader

composer.lock: composer.json
	composer update --optimize-autoloader

.PHONY: clean
clean:
	git clean -fdx
