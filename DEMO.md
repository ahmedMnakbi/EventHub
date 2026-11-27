# EventHub – démonstration de l'avancement (étapes 0 à 4)

Ce document est le scénario de démonstration pour le professeur. Il couvre ce qui est fait aujourd'hui : authentification, comptes, back-office des catégories, lieux et zones, événements (plusieurs images), types de billets par zone. Le modèle tient compte de la correction du professeur. On y montre aussi, en direct, les protections contre XSS, injection SQL et CSRF.

Durée visée : **15 à 20 minutes**, puis les questions.
Chaque membre présente une partie : le sujet l'exige.

| Partie | Présentateur | Durée | Contenu |
|---|---|---|---|
| 1 | **Ahmed Mnakbi** | ~5 min | Projet, modélisation, stack, git ; inscription, connexion, compte, contrôle d'accès |
| 2 | **Saker Fradi** | ~6 min | Catégories, lieux et zones, événements, upload des images, types de billets par zone, règles métier |
| 3 | **Khaled Lourimi** | ~5 min | Attaques en direct sur le back-office (SQLi, CSRF, paramètres falsifiés) ; réservations et suite du projet |

> Les parties sont indicatives : adaptez-les, mais que chacun ait **manipulé lui-même** sa partie avant la démo.

---

## 1. Préparation (la veille, puis 15 minutes avant)

### La veille

- [ ] `git pull` sur `main`, puis `composer install`, `php bin/console importmap:install` et `php bin/console doctrine:migrations:migrate -n` (voir le [README](README.md))
- [ ] **Compte administrateur :** `php bin/console app:create-admin admin@eventhub.tn`
- [ ] **Compte client :** inscrivez-le via le site (`/inscription`), par exemple `client@eventhub.tn`. Ensuite, **déconnectez-vous**.
- [ ] **Données de base**, créées depuis l'admin. Elles servent de décor ; d'autres seront créées en direct.
  - Catégories : `Concerts` (icône `bi-music-note-beamed`), `Conférences` (`bi-mic`), `Festivals` (`bi-stars`)
  - Lieux, chacun découpé en zones depuis sa page détail :
    - `Théâtre Municipal`, Avenue Habib Bourguiba, Tunis, 1350 places : zones `Parterre` (1000) et `Balcon` (350) ;
    - `Cité de la Culture`, Avenue Mohamed V, Tunis, 1800 places : zones `Pelouse` (1500) et `Gradins` (300) ;
    - `Amphithéâtre d'El Jem`, El Jem, 3500 places : zone `Gradins` (3500).
  - Un événement complet, par exemple « Festival de jazz de Carthage » à la Cité de la Culture, avec 2 ou 3 images et ces billets : Standard en Pelouse à 35 DT pour 1200 places, VIP en Pelouse à 90 DT pour 300 places, Étudiant en Gradins à 15 DT pour 300 places. Passez-le en **Publié**.
- [ ] **Fichiers de test** dans un dossier `demo` sur le bureau :
  - `affiche.jpg`, `photo1.jpg` et `photo2.jpg` : de vraies images (JPEG ou PNG, moins de 2 Mo, au moins 300 × 300 px)
  - `faux.jpg` : ouvrez le Bloc-notes, tapez `ceci n'est pas une image`, puis enregistrez sous `faux.jpg` avec le type « Tous les fichiers »
  - `mini.png` : une image de moins de 300 px, une petite icône par exemple
  - `lourde.jpg` : une photo de plus de 2 Mo

  > N'utilisez pas de fichier contenant du code PHP comme « faux » fichier : Windows Defender le met en quarantaine et peut bloquer le serveur pendant la démo.

### 15 minutes avant

- [ ] XAMPP : **MySQL démarré**
- [ ] `symfony serve -d`, puis ouvrez http://127.0.0.1:8000
- [ ] Navigateur en **fenêtre privée** pour la partie client, fenêtre normale pour l'admin. On n'a pas besoin de se déconnecter et reconnecter entre les deux.
- [ ] Ouvrez **phpMyAdmin** (http://localhost/phpmyadmin) sur la base `eventhub`. Il faut qu'Apache soit démarré dans XAMPP.
- [ ] Ouvrez VS Code sur le projet, avec les fichiers listés en partie 5
- [ ] Ouvrez GitHub sur l'onglet **Pull requests → Closed** et sur **Commits**

---

## 2. Partie 1 – Ahmed : le projet et l'authentification

### 2.1 Présentation (1 min 30)

- **Le thème :** une billetterie d'événements. Les visiteurs consultent des événements et réservent des billets ; l'administrateur gère tout depuis un back-office.
- **Pourquoi Events :** la réservation est au cœur du métier (stock de places, types de billets, statuts), l'upload a une place naturelle (les images de l'événement), et les statistiques ont du sens (chiffre d'affaires, billets vendus).
- **Les diagrammes** de cas d'utilisation (client et admin) et de classes :
  - **version 2, corrigée par le professeur** : 10 entités hors User (Category, Venue, Zone, Event, TicketType, Reservation, ReservationItem, Ticket, Payment et Review) ;
  - les changements : plusieurs images par événement, des zones dans chaque lieu, `sold` supprimé (calculé à partir des réservations), des statuts en énumérations, l'avis rattaché à la réservation, le paiement Stripe et des tickets avec QR code ;
  - montrez les compositions Venue ◆ Zone, Event ◆ TicketType, Reservation ◆ ReservationItem et ReservationItem ◆ Ticket.
- **La stack :** Symfony 7.4 LTS, Doctrine, Twig et Bootstrap 5. **Pas d'EasyAdmin**, interdit aux équipes de 3 : tout le back-office est fait à la main.
- **Git, sur GitHub :** une branche et une Pull Request par étape (PR #1 à #3), et des commits explicites.

### 2.2 Inscription sécurisée (1 min 30), en fenêtre privée

1. Allez sur **Inscription**.
2. Prénom : `<script>alert(1)</script>` → **refusé** : « Seules les lettres, espaces, apostrophes et tirets sont autorisés ».
3. Mot de passe : `azerty12` → **refusé**, mot de passe trop faible (contrainte `PasswordStrength`).
4. Deux mots de passe différents → **refusé**.
5. Inscrivez-vous correctement → l'utilisateur est **connecté automatiquement** et redirigé.

> À dire : « Le rôle est forcé côté serveur à `ROLE_USER`. Même en ajoutant un champ `roles` au formulaire avec les outils du navigateur, on ne peut pas devenir admin. »

### 2.3 Mot de passe haché (30 s)

Dans **phpMyAdmin**, ouvrez la table `user` → colonne `password` : `$2y$13$…`
> « Les mots de passe sont hachés en **bcrypt**, jamais stockés en clair. Si un meilleur algorithme devient le défaut, le hash est mis à jour automatiquement à la connexion suivante. »

### 2.4 Espace client (1 min)

1. Ouvrez **Mon compte** : on y voit les informations du profil.
2. **Modifier le profil** : changez le téléphone → enregistré.
3. **Mot de passe** : entrez un mauvais mot de passe actuel → **refusé** (« Mot de passe actuel incorrect »).
   > « Si quelqu'un trouve une session ouverte, il ne peut pas changer le mot de passe sans connaître l'ancien. »

### 2.5 Contrôle d'accès (1 min)

1. Toujours connecté en **client**, tapez l'URL `/admin` → **403 Accès refusé**.
2. Déconnecté, tapez `/compte` → **redirection vers la connexion**.
3. **Brute force :** sur la page de connexion, faites 5 échecs, puis un 6ᵉ essai → **blocage pendant 15 minutes** (`login_throttling`).
   > Faites-le avec un **email inventé**, pas avec le compte admin de la démo.

> À dire : « Il y a deux protections : `access_control` dans `security.yaml`, et `#[IsGranted('ROLE_ADMIN')]` sur chaque contrôleur admin. Si l'une est oubliée, l'autre protège encore. »

---

## 3. Partie 2 – Saker : le back-office et l'upload

Connectez-vous avec le compte **admin**, dans la fenêtre normale.

### 3.1 Tableau de bord et navigation (30 s)

- Montrez la sidebar : les modules faits (Catégories, Lieux, Événements) et les modules prévus, grisés avec leur numéro d'étape.
- Tableau de bord : nombre d'utilisateurs et d'événements.

### 3.2 Catégories et lieux (1 min 30)

1. **Catégories → Nouvelle catégorie** : `Théâtre`, icône `bi-mask` → créée, avec un slug généré automatiquement (`theatre`).
2. Recréez `Théâtre !` → **refusé**, nom trop proche d'une catégorie existante : le slug serait identique.
3. Icône `javascript:alert(1)` → **refusé**, le format est imposé (`bi-…`).
4. **Lieux** : filtrez par ville avec la liste déroulante, et triez par capacité en cliquant sur l'en-tête de colonne.
5. Essayez une capacité de `-10` → **refusé**.
6. Ouvrez un lieu (icône œil) → page détail avec ses **zones** et la jauge « places réparties ».
7. Ajoutez une zone `Fosse` de 100 places dans un lieu déjà entièrement réparti → **refusé** (« Capacité du lieu dépassée : il reste 0 place »).
8. Réduisez la capacité du lieu en dessous du total de ses zones → **refusé**.
9. Supprimez une zone utilisée par des billets → **refusé**.

### 3.3 Événement avec upload des images (2 min 30)

**Événements → Nouvel événement**

1. Ne mettez aucune image → **refusé** (« Ajoutez au moins une image »).
2. Sélectionnez `affiche.jpg` **et** `faux.jpg` → **refusé** (« Formats acceptés : JPEG, PNG ou WebP »). Même une seule mauvaise image bloque tout l'envoi.
   > « Le type est vérifié sur le **contenu réel** du fichier, pas sur son extension. »
3. `mini.png` → **refusé**, image trop petite.
4. `lourde.jpg` → **refusé**, plus de 2 Mo.
5. Date de fin avant la date de début → **refusé**.
6. Statut **Publié** sans aucun billet → **refusé** (« Ajoutez au moins un type de billet avant de publier »).
7. Remplissez correctement, avec `affiche.jpg`, `photo1.jpg` et `photo2.jpg` et le statut **Brouillon** → créé. On arrive sur la **page détail** : la première image sert d'affiche, les autres forment la galerie.
8. **Modifier** → cochez une image à supprimer et ajoutez-en une autre → enregistré. Cochez toutes les images sans en ajouter → **refusé** (« Un événement doit garder au moins une image »).

> À dire sur l'upload :
> - le nom du fichier est **généré par le serveur** (slug + partie aléatoire), l'extension est déduite du contenu ;
> - les fichiers vont dans `public/uploads/events`, et un `.htaccess` y interdit l'exécution de scripts ;
> - une image retirée de l'événement est supprimée du disque ;
> - la liste des images à supprimer n'accepte que les fichiers de l'événement : un nom falsifié comme `../../.env` est refusé ;
> - 8 images au plus, et au moins une.
>
> Montrez le nom du fichier : clic droit sur l'image → « Ouvrir l'image dans un nouvel onglet ».

### 3.4 Types de billets par zone et règles métier (1 min 30)

Sur la page détail de l'événement, créé au Théâtre Municipal (Parterre 1000, Balcon 350) :

1. **Ajouter un billet** : la liste « Zone » ne propose **que les zones du lieu de l'événement**.
2. `Standard`, zone Parterre, prix `-5` → **refusé**.
3. `Standard`, Parterre, 30 DT, **1200 places** → **refusé** (« Capacité de la zone « Parterre » dépassée : il reste 1000 places »).
4. `Standard`, Parterre, 30 DT, 1000 places → ajouté. La jauge de la zone affiche 1000 / 1000.
5. `VIP`, Balcon, 80 DT, 350 places → ajouté.
   > « Chaque type de billet donne accès à une zone ; la zone du ticket se déduit donc de son type. »
6. **Modifier l'événement** → statut **Publié** → accepté, puisqu'il a maintenant des billets.
7. **Modifier l'événement** → changez le lieu → **refusé** : les billets utilisent les zones du lieu actuel.
8. Supprimez le billet **VIP** → supprimé. Supprimez ensuite **Standard**, le dernier billet d'un événement publié → **refusé** (« Repassez d'abord l'événement en brouillon »).

### 3.5 Suppressions protégées (30 s)

1. Dans **Catégories**, supprimez la catégorie de l'événement que vous venez de créer → **refusé** (« … événement(s) utilisent cette catégorie »). C'est la même chose pour un lieu utilisé.
2. Cliquez sur la corbeille, puis **Annuler** dans la boîte de confirmation → rien n'est supprimé.

> À dire : « Une fois les réservations en place (étape 6), un événement, un billet ou une zone déjà réservés ne pourront plus être supprimés : on passera l'événement en **Annulé**, pour garder l'historique. »

---

## 4. Partie 3 – Khaled : attaques en direct, puis la suite

### 4.1 Injection SQL (1 min)

Dans **Catégories** ou **Événements**, tapez dans la recherche :

| Saisie | Résultat attendu |
|---|---|
| `' OR 1=1 --` | **0 résultat**, et pas d'erreur SQL |
| `%` | **0 résultat** (sans protection, le joker `%` renverrait toute la table) |
| `Jazz` | Le bon résultat |

> À dire : « Toutes les requêtes passent par le **QueryBuilder de Doctrine avec des paramètres liés** (`:q`). La saisie n'est jamais concaténée dans le SQL. En plus, on échappe les jokers `%` et `_` du `LIKE`. »
> Montrez [`SearchHelper.php`](src/Repository/SearchHelper.php) et `createAdminListQueryBuilder()` dans [`EventRepository.php`](src/Repository/EventRepository.php).

### 4.2 Paramètres d'URL falsifiés (30 s)

1. Triez la liste des événements par titre. L'URL contient `?sort=e.title&direction=asc`.
2. Remplacez `e.title` par `e.description`, ou par `u.password` dans la liste des catégories → **400 Bad Request**.
   > « Le tri est limité à une **liste blanche** de colonnes ; une colonne non prévue est refusée proprement. »
3. Dans la même URL, mettez `status=nimporte` → le filtre est simplement ignoré (`EventStatus::tryFrom`).

### 4.3 CSRF (1 min 30)

1. Sur la liste des lieux, ouvrez les outils du navigateur (**F12**), puis inspectez le bouton corbeille d'un lieu **non utilisé**.
2. Dans le `<form>`, modifiez la valeur de l'`<input type="hidden" name="_token">`, par exemple en ajoutant `xxx`.
3. Cliquez sur la corbeille et confirmez → **« Jeton de sécurité invalide, suppression annulée »**.
4. Tapez directement l'URL `/admin/lieux/1/supprimer` dans la barre d'adresse → **405 Method Not Allowed**.
   > « Les suppressions se font uniquement en POST, avec un jeton CSRF propre à chaque élément (`delete-venue-ID`). Un lien piégé sur un autre site ne peut rien supprimer. »
5. Tous les formulaires Symfony, y compris la connexion et la déconnexion, ont aussi un jeton CSRF.

### 4.4 XSS (30 s)

1. Créez un événement, ou modifiez-en un, avec le titre `<script>alert('XSS')</script> Gala`.
2. Affichez la liste → le texte apparaît **tel quel**, et aucune alerte ne s'ouvre.
   > « Twig **échappe automatiquement** toute variable affichée. Pour les noms de personnes, on ajoute une validation stricte : défense en profondeur. »
3. Remettez un titre normal après la démonstration.

### 4.5 La suite du projet (1 min 30)

Revenez au **diagramme de classes** et présentez ce qui arrive :

- **Étape 5, catalogue client :** recherche et filtres par mot-clé, catégorie, ville, date et prix, page détail, « à partir de X DT ».
- **Étape 6, panier, réservation et paiement :**
  - panier en session pour **un seul événement** ;
  - validation **transactionnelle** : les places restantes sont **calculées** (quantité − billets réservés non annulés), comme l'a demandé le professeur, sans champ `sold` ;
  - paiement **Stripe Checkout** en mode test : aucune donnée bancaire ne passe par notre serveur ;
  - génération d'un **ticket par place** : n° de série unique, QR code, PDF ;
  - « Mes réservations » protégée par un **Voter** : un client ne voit que ses propres réservations.
- **Étape 7 :** admin des réservations (confirmer ou annuler, ce qui remet les places en vente) et des clients (activer ou désactiver un compte ; le `UserChecker` qui bloque un compte désactivé est déjà en place).
- **Étape 8 :** un avis par réservation **confirmée** (l'utilisateur et l'événement se déduisent de la réservation), avec modération.
- **Étape 9 :** statistiques avec Chart.js (chiffre d'affaires, billets vendus, top des événements).
- **Étapes 10 et 11 :** données de démo, en-têtes de sécurité (CSP), pages d'erreur personnalisées, **mode production**, hébergement.

---

## 5. Code à montrer si le professeur le demande

| Sujet | Fichier |
|---|---|
| Configuration de sécurité (hash, CSRF, throttling, access_control) | [`config/packages/security.yaml`](config/packages/security.yaml) |
| Blocage d'un compte désactivé | [`src/Security/UserChecker.php`](src/Security/UserChecker.php) |
| Rôle forcé à l'inscription | [`src/Controller/RegistrationController.php`](src/Controller/RegistrationController.php) |
| Contraintes de mot de passe | [`src/Form/PasswordConstraints.php`](src/Form/PasswordConstraints.php) |
| Règles métier dans l'entité | [`src/Entity/Event.php`](src/Entity/Event.php) (`validateBusinessRules`) et [`src/Entity/TicketType.php`](src/Entity/TicketType.php) |
| Contraintes de l'upload | [`src/Form/EventType.php`](src/Form/EventType.php) (`Image`) |
| Enregistrement sécurisé du fichier | [`src/Service/FileUploader.php`](src/Service/FileUploader.php) |
| Pas d'exécution dans les uploads | [`public/uploads/.htaccess`](public/uploads/.htaccess) |
| Requêtes paramétrées | [`src/Repository/EventRepository.php`](src/Repository/EventRepository.php), [`src/Repository/SearchHelper.php`](src/Repository/SearchHelper.php) |
| Tri en liste blanche | [`src/Controller/Admin/SafePaginationTrait.php`](src/Controller/Admin/SafePaginationTrait.php) |
| Suppression POST + CSRF + règles | [`src/Controller/Admin/EventController.php`](src/Controller/Admin/EventController.php) (`delete`) |
| Bouton de suppression réutilisable | [`templates/admin/_delete_form.html.twig`](templates/admin/_delete_form.html.twig) |
| Confirmation sans JavaScript inline | [`assets/controllers/confirm_controller.js`](assets/controllers/confirm_controller.js) |

---

## 6. Questions probables et réponses

**Pourquoi Symfony 7.4 et pas 8 ?**
XAMPP fournit PHP 8.2, et Symfony 8 exige PHP 8.4. La 7.4 est une version **LTS**, maintenue plusieurs années.

**Comment est fait l'admin sans EasyAdmin ?**
Des contrôleurs, formulaires et templates Twig écrits à la main. Les briques communes (barre de recherche, bouton de suppression sécurisé, pagination sûre) sont factorisées et réutilisées par chaque module.

**Où sont stockés les prix et pourquoi ?**
En `DECIMAL(10,2)`, jamais en `float`, qui produit des erreurs d'arrondi sur l'argent.

**Que se passe-t-il si deux personnes achètent la dernière place en même temps ?**
C'est prévu à l'étape 6 : la validation du panier se fera dans une **transaction**, avec un contrôle du stock restant juste avant d'enregistrer.

**Pourquoi un slug ?**
Pour des URL lisibles côté client, par exemple `/evenements/12-festival-de-jazz`. Il est calculé à partir du nom, jamais saisi par l'utilisateur.

**Le projet est en mode dev, pourquoi ?**
C'est l'état actuel du développement : on voit la barre de debug Symfony. Le passage en **mode production** (`APP_ENV=prod`, cache, pages d'erreur personnalisées) et l'hébergement sont prévus aux étapes 10 et 11.

**Comment vous êtes-vous organisés ?**
Une branche et une Pull Request par fonctionnalité, fusionnées dans `main`. Chacun commite avec son propre compte. Le détail est dans [PLAN.md](PLAN.md).

---

## 7. Plan B en cas de problème

| Problème pendant la démo | Réaction |
|---|---|
| Page blanche ou erreur de connexion à la base | Vérifiez que MySQL est démarré dans XAMPP |
| Le site ne répond plus | `symfony server:stop`, puis `symfony serve -d` |
| « Trop de tentatives » sur le compte admin | Utilisez la fenêtre privée avec le compte client, ou lancez `php bin/console cache:clear` |
| Une modification n'apparaît pas | `php bin/console cache:clear` |
| Upload refusé alors que l'image semble bonne | Vérifiez la taille (moins de 2 Mo), les dimensions (300 à 4000 px) et le format (JPEG, PNG ou WebP) |
