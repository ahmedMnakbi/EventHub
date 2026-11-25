# EventHub – billetterie et réservation d'événements (Symfony 7.4)

Projet du module **Développement des applications sécurisées**.
Équipe : Ahmed Mnakbi, Saker Fradi, Khaled Lourimi. Le plan complet est dans [PLAN.md](PLAN.md).

## Prérequis

- XAMPP (PHP 8.2 et MariaDB 10.4), avec MySQL démarré
- Dans `C:\xampp\php\php.ini`, ces lignes doivent être décommentées : `extension=intl`, `extension=gd`, `extension=zip`, `extension=sodium` et `zend_extension=opcache`
- [Composer](https://getcomposer.org/) et [Symfony CLI](https://symfony.com/download)

Pour vérifier l'installation : `symfony check:requirements`

## Installation (première fois)

```bash
git clone https://github.com/ahmedMnakbi/EventHub.git
cd EventHub
composer install
php bin/console importmap:install
```

Ensuite, créez un fichier **`.env.local`** (il n'est jamais commité) qui contient votre connexion à la base :

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/eventhub?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

Puis créez la base et les tables :

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
```

## Lancer le projet

```bash
symfony serve -d
```

Le site est alors sur http://127.0.0.1:8000.

## Créer un administrateur

```bash
php bin/console app:create-admin admin@eventhub.tn
```

Le mot de passe est demandé de façon masquée. Si l'email existe déjà, ce compte est simplement promu administrateur.
Le back-office est accessible sur `/admin`.

## Workflow git

1. `git checkout main && git pull`
2. `git checkout -b feature/<nom>` (par exemple `feature/events`)
3. Faites des commits réguliers **avec votre propre compte git** (`git config user.name` et `user.email`)
4. `git push -u origin feature/<nom>`, puis ouvrez une Pull Request vers `main`

Après chaque `git pull` qui apporte de nouvelles migrations, lancez : `php bin/console doctrine:migrations:migrate -n`

## Routes principales

| URL | Rôle requis | Description |
|---|---|---|
| `/` | public | Accueil |
| `/inscription` | public | Création de compte |
| `/connexion` / `/deconnexion` | public | Authentification |
| `/compte` | `ROLE_USER` | Profil, modification, mot de passe |
| `/admin` | `ROLE_ADMIN` | Tableau de bord administrateur |
| `/admin/categories` | `ROLE_ADMIN` | CRUD des catégories |
| `/admin/lieux` | `ROLE_ADMIN` | CRUD des lieux |
| `/admin/evenements` | `ROLE_ADMIN` | CRUD des événements (upload de l'affiche), types de billets depuis la page détail |

## Uploads

Les affiches des événements sont stockées dans `public/uploads/events/` (ignoré par git, créé automatiquement au premier upload).
Seules les images JPEG, PNG et WebP de 2 Mo maximum sont acceptées : le type est vérifié sur le contenu réel du fichier, et le nom est généré par le serveur.
Le fichier `public/uploads/.htaccess` interdit l'exécution de scripts dans ce dossier quand l'application est hébergée sous Apache.
