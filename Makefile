all: vendor

vendor: composer.lock
	composer install

composer.lock: composer.json
	composer update

.PHONY: clean
clean:
	git clean -fdx
