# Makefile - HubLim (Windows / macOS / Linux via Docker)

# --- Variables ---
# On récupère dynamiquement l'ID de votre utilisateur local (ex: 1000:1000)
HOST_USER = $(shell id -u):$(shell id -g)

ifneq (,$(wildcard ./.env.local))
    DOCKER_COMP = docker compose --env-file .env --env-file .env.local
else
    DOCKER_COMP = docker compose
endif

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

## Lance une commande Composer (Créera les fichiers avec VOS droits)
comp:
	$(DOCKER_COMP) exec -u $(HOST_USER) web composer $(c)

# --- Commandes Projet (Symfony & Composer) ---

## [LEAD DEV UNIQUEMENT] Initialise le projet Symfony 8.0 vide depuis zéro
init-project:
	@echo "Création du squelette Symfony 8.0..."
	$(DOCKER_COMP) exec -u 0 web composer create-project symfony/skeleton:"^8.0" .
	@echo "Installation du pack Webapp..."
	$(DOCKER_COMP) exec -u 0 web composer require webapp
	@echo "Correction des permissions..."
	$(DOCKER_COMP) exec -u 0 web chown -R www-data:www-data /var/www/html
	@echo "Le projet est initialisé ! Pensez à commiter les fichiers générés."

## Installation complète (Composer + Database + Assets)
install:
## Installation complète (Composer + Database + Assets)
install:
	@echo "--- 1. Configuration de l'environnement local ---"
	@if [ ! -f .env.local ]; then \
		echo "⚠️ ATTENTION : Le fichier .env.local n'existe pas." ; \
		echo "👉 Création d'un modèle de base..." ; \
		echo "DB_ROOT_PASSWORD=votre_mot_de_passe_root_ici" > .env.local ; \
		echo "DB_PASSWORD=votre_mot_de_passe_user_ici" >> .env.local ; \
		echo "PHPMYADMIN_PORT=8888" >> .env.local ; \
		echo "DATABASE_URL=\"mysql://hublim_root:votre_mot_de_passe_user_ici@db:3306/hublim_db?serverVersion=11.4.10-MariaDB\"" >> .env.local ; \
		echo "🛑 ERREUR FATALE : Veuillez remplir vos vrais mots de passe dans le nouveau fichier .env.local avant de relancer 'make install'." ; \
		exit 1 ; \
	fi

	@echo "--- 2. Fix des permissions locales ---"
# 	$(DOCKER_COMP) exec -u 0 web chmod 644 .env.local
	chmod 644 .env.local

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
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration

	@echo "Chargement des fausses données (Fixtures)..."
	$(SYMFONY) doctrine:fixtures:load --no-interaction

## Lance une commande Symfony arbitraire (Créera les fichiers avec VOS droits)
sf:
	$(DOCKER_COMP) exec -u $(HOST_USER) web php bin/console $(c)

## Vide le cache Symfony
cc:
	$(SYMFONY) cache:clear