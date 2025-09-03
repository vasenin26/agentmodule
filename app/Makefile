#only in container

watch:
	@echo "Starting PHP worker with hot reload..."
	@while inotifywait -e modify,create,delete -r ./src; do \
		echo "Change detected, restarting worker..."; \
		pkill -f "php main.php" || true; \
		php main.php & \
	done