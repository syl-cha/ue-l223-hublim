# HubLim

## Présentation
[HubLim](https://hublim.bradype.fr) est une application web destinée au public universitaire ou étudiant de l'[Université de Limoges](https://www.unilim.fr/).
Plateforme d'échanges communautaires permettant la publication d'annonces, la discussion via des fils de réactions, et la gestion des profils (filières, statuts, départements). Intègre un système de modération complet.

![Accueil HubLim](docs/images/hublim_accueil.png)

## Fonctionnalités Principales
* **Annonces (Cards)** : Publication avec titre, description, images, catégories et public cible. Mise en favoris.
* **Discussions (Messages)** : Fils de réactions sous chaque annonce.
* **Édition Riche** : Support du [Markdown](https://commonmark.org/help/) pour les descriptions et les messages. Prise en compte de $\LaTeX$ pour les [formules mathématiques](https://www.upyesp.org/posts/makrdown-vscode-math-notation/#latex-cheat-sheet).
* **Gestion des Médias** : Upload local des images d'illustration.
* **Modération** : Système de signalements (Reports), notifications par email (auteurs et administrateurs), interface de traitement des litiges.
* **Profils** : Association stricte des utilisateurs à des filières d'études et/ou des statuts universitaires.
* **Fixtures** : Initialisation des données de base (filières, départements) via sources JSON.

## Stack Technique & Librairies

### Backend
* [PHP 8.4+](https://www.php.net/)
* [Symfony 8.0](https://symfony.com/)
* [MariaDB](https://mariadb.org/)
* [Doctrine ORM](https://www.doctrine-project.org/)

### Frontend
* [Twig](https://twig.symfony.com/) : Moteur de templates.
* [Bootstrap 5](https://getbootstrap.com/) : Framework CSS utilitaire et composants UI.
* [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html) : Gestion native des assets (sans Webpack/Node.js).
* [Symfony UX Turbo](https://symfony.com/doc/current/ux/turbo.html) : Navigation fluide via Hotwire.
* [Stimulus](https://stimulus.hotwired.dev/) : Micro-framework JavaScript.

### Utilitaires
* [League HTML-to-Markdown](https://github.com/thephpleague/html-to-markdown) : Conversion de contenu.
* [Twig Markdown Extra](https://twig.symfony.com/doc/3.x/filters/markdown_to_html.html) : Rendu Markdown sécurisé.
* [Symfony Mailer](https://symfony.com/doc/current/mailer.html) : Envoi de notifications.
* [SchebTwoFactorBundle](https://symfony.com/bundles/SchebTwoFactorBundle/current/index.html)

### Infrastructure
* [Docker](https://www.docker.com/) : Conteneurisation (Web, MariaDB, phpMyAdmin).

## Architecture des Données
* **`User`** : Utilisateur rattaché à une filière (`StudyField`) et un statut (`Status`).
* **`Card`** : Annonce publiée.
* **`Message`** : Réponse ou commentaire lié à une `Card`.
* **`Department`** & **`StudyField`** : Référentiel des départements et de leurs filières associées.
* **`Report`** : Signalement utilisateur pour modération d'une `Card` ou d'un `Message`.

## Installation & Utilisation

L'environnement de développement repose entièrement sur Docker et un `Makefile`.

### 1. Démarrer l'infrastructure
Lancement des conteneurs Docker (Web, Base de données) :
```bash
make up
```

### 2. Installation
Installation des dépendances (Composer) et initialisation :
```bash
make install
```

### 3. Base de données (Développement)
Purge, recréation de la structure et chargement des fixtures :
```bash
make db-hard-reset
```

### Commandes utiles (Makefile)
* Exécuter une commande Symfony CLI : `make sf c="<commande>"`
* Gérer les dépendances frontend (AssetMapper) : `make sf c="importmap:require <package>"`
* Arrêter les conteneurs : `make down`

## Conventions de Développement
* **Workflow** : Utilisation exclusive du `Makefile`.
* **Frontend** : Pas de `npm` ou `yarn`. Utiliser `importmap` et les contrôleurs Stimulus dans `src/assets/controllers/`.
* **UI/UX** : Privilégier les classes utilitaires Bootstrap 5 et `<turbo-frame>` pour l'asynchronisme. Pas de CSS custom hors absolue nécessité.
* **Code** : Typage strict, attributs PHP 8 (`#[]`) pour le mapping Doctrine et le routage.
