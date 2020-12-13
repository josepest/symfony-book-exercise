SHELL := /bin/bash

tests:
	symfony console doctrine\:fixtures\:load -n
	symfony php bin/phpunit
.PHONY: tests


# BACKUP DB COMMAND LINE
# symfony run pg_dump --data-only > dump.sql

# RESTORE DB COMMAND LINE
# symfony run psql < dump.sql

# ACCESO A SERVICIO DB
# docker-compose exec database psql main
# symfony run psql

# ASYNC MESSAGE CONSUMER
# symfony console messenger:consume async -vv
# guest/ guest 
# symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async
# symfony console messenger:failed:show
# symfony console messenger:failed:retry
# symfony server:status
# symfony open:local:rabbitmq
# http://127.0.0.1:15672

# ENCORE
# symfony run -d yarn encore dev --watch
# server spa:
# symfony server:start -d --passthru=index.html
# API_ENDPOINT=`symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL --dir=..` yarn encore dev
# API_ENDPOINT=`symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL --dir=..` symfony run -d --watch=webpack.config.js yarn encore dev --watch
# setup CORS_ALLOW_ORIGIN en env

# TRANS
# symfony console translation:update fr --force --domain=messages