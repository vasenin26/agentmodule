bash:
	docker-compose run --rm -u local agentmodule bash

test:
	docker-compose run --rm -u local agentmodule ./vendor/bin/phpunit

start:
	docker-compose run --rm -u local agentmodule php main.php