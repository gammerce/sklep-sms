.PHONY: artisan test setup\:test

test:
	docker-compose exec app ./vendor/bin/phpunit $(ARGS)

test\:unit:
	docker-compose exec app ./vendor/bin/phpunit tests/Unit/ $(ARGS)

artisan:
	docker-compose exec app php artisan $(ARGS)

setup\:test:
	$(MAKE) artisan ARGS="test:setup"
