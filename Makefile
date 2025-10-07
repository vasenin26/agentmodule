bash:
	docker-compose run --rm -u local -p 8484:8484 agentmodule bash

test:
	docker-compose run --rm -u local agentmodule ./vendor/bin/phpunit

start:
	docker-compose run --rm -u local agentmodule php main.php

build-prod:
	docker build -t agentmodule:latest -f docker/agent/Dockerfile --target production .
build-orchestrated:
	docker build -t agentmodule:latest -f docker/agent/Dockerfile --target orchestrated .

.PHONY: bump-patch
bump-patch:
	$(eval LATEST_TAG := $(shell git describe --tags --abbrev=0))

	$(eval MAJOR := $(word 1,$(subst ., ,$(LATEST_TAG:v%=%))))
	$(eval MINOR := $(word 2,$(subst ., ,$(LATEST_TAG))))
	$(eval PATCH := $(word 3,$(subst ., ,$(LATEST_TAG))))

	$(eval NEW_TAG := v$(MAJOR).$(MINOR).$(shell echo $$(($(PATCH)+1))))

	@echo "Текущий тег: $(LATEST_TAG)"
	@echo "Новый тег:   $(NEW_TAG)"

	git tag -a $(NEW_TAG) -m "Bump version to $(NEW_TAG)"

	git push origin $(NEW_TAG)

.PHONY: bump-minor
bump-minor:
	$(eval LATEST_TAG := $(shell git describe --tags --abbrev=0))

	$(eval MAJOR := $(word 1,$(subst ., ,$(LATEST_TAG:v%=%))))
	$(eval MINOR := $(word 2,$(subst ., ,$(LATEST_TAG))))

	$(eval NEW_TAG := v$(MAJOR).$(shell echo $$(($(MINOR)+1))).0)

	@echo "Текущий тег: $(LATEST_TAG)"
	@echo "Новый тег:   $(NEW_TAG)"

	git tag -a $(NEW_TAG) -m "Bump minor version to $(NEW_TAG)"

	git push origin $(NEW_TAG)