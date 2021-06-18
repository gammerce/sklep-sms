.PHONY: artisan test setup\:test

test:
	docker-compose exec app ./vendor/bin/phpunit $(ARGS)

artisan:
	docker-compose exec app php artisan $(ARGS)

setup\:test:
	$(MAKE) artisan ARGS="test:setup"
