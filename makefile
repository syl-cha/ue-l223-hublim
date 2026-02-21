# Makefile - HubLim (Windows / macOS / Linux via Docker)

# --- Variables ---
DOCKER_COMP = docker compose
# On cible le conteneur 'web' défini dans notre docker-compose.yml
PHP_CONT = $(DOCKER_COMP) exec -u www-data web php
SYMFONY = $(PHP_CONT) bin/console

# --- Commandes Docker ---

## Démarre le projet (et reconstruit l'image si la config a changé)
up:
	$(DOCKER_COMP) up -d --build

## Arrête les conteneurs
down:
	$(DOCKER_COMP) down

## Affiche les logs en temps réel (utile pour débuggage)
logs:
	$(DOCKER_COMP) logs -f

## Accès terminal (bash) dans le conteneur web
bash:
	$(DOCKER_COMP) exec -it web bash

# --- Commandes Projet (Symfony & Composer) ---

## [LEAD DEV UNIQUEMENT] Initialise le projet Symfony 8.0 vide depuis zéro
init-project:
	@echo "Création du squelette Symfony 8.0..."
	$(DOCKER_COMP) exec -u www-data web composer create-project symfony/skeleton:"^8.0" .
	@echo "Installation du pack Webapp..."
	$(DOCKER_COMP) exec -u www-data web composer require webapp
	@echo "Le projet est initialisé ! Pensez à commiter les fichiers générés."

## Installation complète (Composer + Database + Assets)
install:
	@echo "--- 1. Configuration de l'environnement local ---"
	$(DOCKER_COMP) exec -u www-data web sh -c 'if [ ! -f .env.local ]; then \
		echo "DATABASE_URL=\"mysql://hublim_user:hublim_secure_pwd@db:3306/hublim_db?serverVersion=11.4.10-MariaDB\"" > .env.local; \
	fi'

	@echo "--- 2. Fix des permissions locales ---"
	$(DOCKER_COMP) exec -u 0 web chmod 644 .env.local

	@echo "--- 3. Installation des dépendances (Composer) ---"
	$(DOCKER_COMP) exec -u 0 web composer install
	$(DOCKER_COMP) exec -u 0 web chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

	@echo "--- 4. Initialisation de la Base de Données MariaDB ---"
	$(MAKE) db-reset

	@echo "--- 5. Installation des Assets (Bootstrap / Importmap) ---"
	$(SYMFONY) importmap:install
	@echo "Installation terminée avec succès ! L'application est disponible sur http://localhost:8008"

## Réinitialise complètement la BDD MariaDB avec de fausses données (Fixtures)
db-reset:
	@echo "Suppression de toutes les tables de la base de données..."
	$(SYMFONY) doctrine:schema:drop --full-database --force

	@echo "Exécution des migrations SQL (Création des nouvelles tables)..."
	$(SYMFONY) doctrine:migrations:migrate --no-interaction

	@echo "Chargement des fausses données (Fixtures)..."
	$(SYMFONY) doctrine:fixtures:load --no-interaction

## Lance une commande Symfony arbitraire (ex: make sf c="make:controller")
sf:
	$(SYMFONY) $(c)

## Vide le cache Symfony
cc:
	$(SYMFONY) cache:clear