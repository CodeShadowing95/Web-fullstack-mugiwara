### Variables ###
PHP_SERVICE = php

### Docker ###
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f

version: ##= Affiche la version de Symfony
	docker compose exec $(PHP_SERVICE) php bin/console --version

### Symfony via Docker ###
console:
	docker compose exec $(PHP_SERVICE) php bin/console

migrate: ## Applique les migrations sans interaction (automatique)
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction

migrate-safe: ## Applique les migrations avec confirmation (manuel)
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate

schema-update: ## Met à jour le schéma de la base de données
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:schema:update --force

fixtures: ## Charge les fixtures (données fictives comme des utilisateurs, des articles, etc.) dans la base de données
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:fixtures:load --no-interaction

db-create: ## Crée la base de données si elle n'existe pas
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:database:create --if-not-exists

db-drop: ## Supprime la base de données (attention, cela supprime toutes les données)
	docker compose exec $(PHP_SERVICE) php bin/console doctrine:database:drop --force --if-exists

### Remise à zéro rapide (sans confirmation)
fresh: db-drop db-create migrate fixtures

### Remise à zéro avec confirmation manuelle lors des migrations
fresh-safe: db-drop db-create migrate-safe fixtures

### Helpers ###
reset-db: db-drop db-create migrate fixtures

### Composer ###
composer-install:
	docker compose exec $(PHP_SERVICE) composer install

### Clear cache ###
cache-clear:
	docker compose exec $(PHP_SERVICE) php bin/console cache:clear

### Passphrase ###
passphrase:
	docker compose exec $(PHP_SERVICE) php bin/console lexik:jwt:generate-keypair --overwrite
