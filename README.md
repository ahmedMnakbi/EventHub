# EventHub – billetterie et réservation d'événements

Application **Symfony 7.4 LTS** réalisée pour le module **Développement des applications sécurisées**.
Les visiteurs consultent des événements (concerts, conférences, festivals…) et réservent des billets. Un back-office permet de tout administrer.

**Équipe :** Ahmed Mnakbi · Saker Fradi · Khaled Lourimi

| Document | Contenu |
|---|---|
| [PLAN.md](PLAN.md) | Choix du thème, modélisation, règles métier, répartition du travail, étapes |
| [DEMO.md](DEMO.md) | Scénario de démonstration de l'état actuel pour le professeur |

## Avancement

| Étape | Contenu | État |
|---|---|---|
| 0–1 | Environnement et initialisation du projet | ✅ |
| 2 | Inscription, connexion, compte client, layouts front et admin | ✅ |
| 3 | Catégories, lieux et leurs zones (CRUD admin) | ✅ |
| 4 | Événements (plusieurs images) et types de billets par zone | ✅ |
| 4 bis | Modèle v2 après correction du professeur (zones, images, sans `sold`) | ✅ |
| 5 | Catalogue client : recherche, filtres, page détail | ⏳ |
| 6 | Panier, réservation, paiement Stripe, tickets (QR code, PDF), « Mes réservations » | ⏳ |
| 7 | Admin : réservations et clients | ⏳ |
| 8 | Avis et modération | ⏳ |
| 9 | Statistiques (Chart.js) | ⏳ |
| 10–11 | Données de démo, durcissement, pages d'erreur, mise en production et hébergement | ⏳ |

## Stack

- PHP 8.2 et MariaDB 10.4 (XAMPP), Symfony 7.4 LTS, Doctrine ORM et Migrations
- Twig avec Bootstrap 5 et Bootstrap Icons, AssetMapper (**pas de Node ni de npm**), Stimulus et Turbo
- KnpPaginator (pagination et tri), NelmioSecurityBundle (en-têtes HTTP de sécurité)
- **Aucun bundle d'administration** (pas d'EasyAdmin) : le sujet l'interdit aux équipes de 3, tout le back-office est écrit à la main

---

## Installation sur un nouveau PC (Windows)

Suivez ces étapes une seule fois. Comptez environ 15 minutes.

### 1. Installer les outils

| Outil | Où le trouver | Remarque |
|---|---|---|
| **Git** | https://git-scm.com/download/win | Options par défaut |
| **XAMPP** (PHP 8.2) | https://www.apachefriends.org | Installer dans `C:\xampp` |
| **Composer** | https://getcomposer.org/Composer-Setup.exe | Quand l'installeur demande PHP, choisir `C:\xampp\php\php.exe`. Cochez l'ajout au PATH |
| **Symfony CLI** | https://symfony.com/download (onglet Windows, binaire amd64) | Décompressez `symfony.exe` dans un dossier du PATH, par exemple `C:\symfony`, puis ajoutez ce dossier au PATH |

Ouvrez ensuite un **nouveau** terminal et vérifiez que chaque outil répond :

```powershell
php -v               # doit afficher PHP 8.2.x
composer --version
symfony version
git --version
```

> Si `php` n'est pas reconnu, ajoutez `C:\xampp\php` au PATH : Paramètres → Système → Informations système → Paramètres avancés → Variables d'environnement → Path → Nouveau.

### 2. Configurer PHP

Ouvrez `C:\xampp\php\php.ini` dans un éditeur de texte. Sauvegardez d'abord une copie du fichier.

1. **Activez les extensions** en retirant le `;` au début de ces lignes :
   ```ini
   extension=gd
   extension=intl
   extension=sodium
   extension=zip
   ```
2. **Ajoutez** cette ligne si elle n'existe pas : `zend_extension=opcache`
3. **Vérifiez** que `post_max_size` est plus grand que `upload_max_filesize`. Par exemple : `upload_max_filesize=40M` et `post_max_size=45M`.

Contrôlez ensuite que tout est en ordre :

```powershell
symfony check:requirements
```

Le résultat doit être « Your system is ready to run Symfony projects ».

### 3. Démarrer MySQL

Ouvrez le **XAMPP Control Panel** et cliquez sur **Start** en face de **MySQL**. Apache n'est pas nécessaire : Symfony a son propre serveur.

### 4. Récupérer le projet

Acceptez d'abord l'invitation GitHub reçue par email. Ensuite :

```powershell
cd $HOME\Desktop
git clone https://github.com/ahmedMnakbi/EventHub.git
cd EventHub

# Vos commits doivent être à votre nom (c'est noté par le professeur)
git config user.name "Prénom Nom"
git config user.email "email-de-votre-compte-github@exemple.com"

composer install
php bin/console importmap:install
```

`composer install` télécharge les dépendances PHP dans `vendor/`. `importmap:install` télécharge Bootstrap, Chart.js… dans `assets/vendor/`. Ces deux dossiers ne sont pas dans git, c'est normal.

### 5. Créer la base de données

La connexion par défaut est déjà dans `.env` : utilisateur `root`, **sans mot de passe**, base `eventhub`. C'est la configuration standard de XAMPP.

```powershell
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
```

> **Votre MySQL a un mot de passe ou un autre port ?** Créez un fichier `.env.local` à la racine du projet. Il n'est jamais commité. Mettez-y votre propre ligne :
> ```dotenv
> DATABASE_URL="mysql://root:VOTRE_MOT_DE_PASSE@127.0.0.1:3306/eventhub?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
> ```

### 6. Créer votre compte administrateur

```powershell
php bin/console app:create-admin votre.email@exemple.com
```

La commande demande un prénom, un nom et un mot de passe. Le mot de passe est masqué pendant la saisie. Il faut au moins 8 caractères et un mot de passe assez robuste, par exemple avec des majuscules, des chiffres et des symboles.

### 7. Lancer le site

```powershell
symfony serve -d
```

- Site client : http://127.0.0.1:8000
- Back-office : http://127.0.0.1:8000/admin (connectez-vous avec le compte créé à l'étape 6)

Pour arrêter le serveur : `symfony server:stop`.

---

## Au quotidien

### Après chaque `git pull`

Un collègue a pu ajouter des dépendances ou des tables. Lancez ces trois commandes, elles ne font rien si rien n'a changé :

```powershell
composer install
php bin/console importmap:install
php bin/console doctrine:migrations:migrate -n
```

### Workflow git

1. `git checkout main` puis `git pull`
2. `git checkout -b feature/<nom>` (par exemple `feature/catalogue`)
3. Faites des commits réguliers, avec un message clair en français
4. `git push -u origin feature/<nom>`, puis ouvrez une **Pull Request** vers `main` sur GitHub
5. Une fois la PR fusionnée : `git checkout main`, `git pull`, puis supprimez la branche locale

On ne commite jamais directement sur `main`.

### Commandes utiles

| Commande | Rôle |
|---|---|
| `php bin/console cache:clear` | Vider le cache, si une modification ne s'affiche pas |
| `php bin/console debug:router` | Lister toutes les routes |
| `php bin/console make:migration` | Générer une migration après une modification d'entité |
| `php bin/console doctrine:schema:validate` | Vérifier que la base correspond aux entités |
| `php bin/console lint:twig templates` | Vérifier la syntaxe des templates |
| `symfony server:log` | Voir les logs du serveur en direct |

---

## Dépannage

| Problème | Solution |
|---|---|
| `could not find driver` | Dans `php.ini`, vérifiez que `extension=pdo_mysql` n'est pas commenté |
| `SQLSTATE[HY000] [2002] Aucune connexion…` | MySQL n'est pas démarré : bouton Start dans XAMPP |
| `Access denied for user 'root'` | Votre MySQL a un mot de passe : créez `.env.local` (voir l'étape 5) |
| `Unknown database 'eventhub'` | `php bin/console doctrine:database:create` |
| `Table … doesn't exist` après un `git pull` | `php bin/console doctrine:migrations:migrate -n` |
| Page sans style (pas de Bootstrap) | `php bin/console importmap:install` |
| `Class … not found` après un `git pull` | `composer install` |
| L'extension `intl` ou `gd` est manquante | Décommentez-la dans `C:\xampp\php\php.ini`, puis relancez le terminal et `symfony serve` |
| Le port 8000 est déjà utilisé | `symfony serve -d --port=8001` |
| « Trop de tentatives de connexion » | La protection anti brute-force bloque pendant 15 minutes après 5 échecs. Attendez, ou lancez `php bin/console cache:clear` |
| Une modification n'apparaît pas | `php bin/console cache:clear` |

---

## Routes principales

| URL | Accès | Description |
|---|---|---|
| `/` | public | Accueil |
| `/inscription` | public | Création de compte |
| `/connexion`, `/deconnexion` | public | Authentification |
| `/compte` | `ROLE_USER` | Profil, modification, changement de mot de passe |
| `/admin` | `ROLE_ADMIN` | Tableau de bord |
| `/admin/categories` | `ROLE_ADMIN` | CRUD des catégories |
| `/admin/lieux` | `ROLE_ADMIN` | CRUD des lieux ; zones gérées depuis la page détail du lieu |
| `/admin/evenements` | `ROLE_ADMIN` | CRUD des événements avec upload de plusieurs images ; types de billets, chacun dans une zone, gérés depuis la page détail |

## Uploads

Les images des événements sont stockées dans `public/uploads/events/` (de 1 à 8 par événement ; la première sert d'affiche). Ce dossier est ignoré par git et créé automatiquement au premier upload : **chacun a donc ses propres images en local.**

- Seules les images JPEG, PNG et WebP de 2 Mo maximum, entre 300 et 4000 px de côté, sont acceptées.
- Le type est vérifié sur le contenu réel du fichier, et le nom est généré par le serveur.
- `public/uploads/.htaccess` interdit l'exécution de scripts dans ce dossier quand l'application est hébergée sous Apache.
