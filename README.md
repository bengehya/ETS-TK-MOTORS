# ETS TK MOTORS — TK MOTORS

Application métier de gestion pour **ETS TK MOTORS** (Kinshasa).

> « Votre Moto, Notre Passion ! »

Cette première phase pose uniquement la fondation : stack, organisation, authentification, rôles, sécurité de session et tableau de bord. Les modules stock, ventes, caisse et rapports ne sont pas encore développés.

## Stack

- Laravel 12
- PHP 8.3+
- Vue 3
- Inertia.js
- Tailwind CSS
- MySQL
- Vite

## Prérequis

- PHP 8.3 ou supérieur, avec les extensions `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `pdo_mysql`
- Composer
- Node.js 18+ et npm
- MySQL 8

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer MySQL dans `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tk_motors
DB_USERNAME=tk_motors
DB_PASSWORD=
SESSION_LIFETIME=5
```

Puis :

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Le premier compte créé devient automatiquement le **BOSS_PRINCIPAL** de l’organisation ETS TK MOTORS. L’inscription publique est ensuite fermée.

L’application et la production restent sur **MySQL**. Les migrations métier ne sont pas adaptées à SQLite. PHPUnit utilise une base en mémoire, uniquement pour les tests.

## Test via un tunnel HTTPS

L’application répond en localhost et derrière un tunnel HTTPS, par exemple ngrok. Aucune URL de tunnel n’est écrite dans le code : elle change à chaque session.

1. Laisser `APP_URL=http://localhost` pour Artisan. Les pages web utilisent l’hôte de la requête.
2. Laisser `TRUSTED_PROXIES=*` en local. En production, indiquer l’adresse réelle du proxy, ou laisser la variable vide si l’application est jointe directement.
3. Ne pas définir `SESSION_SECURE_COOKIE` : le cookie est Secure uniquement lorsque la requête est en HTTPS.
4. Ne pas définir `ASSET_URL` ni `VITE_DEV_SERVER_URL` avec une URL de tunnel.
5. Compiler les assets, puis retirer `public/hot` s’il a été créé par `npm run dev` :

```bash
npm run build
rm -f public/hot
php artisan serve
```

6. Ouvrir le tunnel vers le port affiché par `php artisan serve`, puis utiliser l’URL HTTPS que l’outil affiche. Le localhost continue de fonctionner en parallèle.

## Sécurité de session

La session expire réellement après **5 minutes d’inactivité**. Une route ou une API protégée refuse alors l’accès : il faut se reconnecter.

## Rôles

| Rôle | Description |
| --- | --- |
| `BOSS_PRINCIPAL` | Premier compte de l’organisation. Peut plus tard inviter un second patron. |
| `BOSS_SECONDAIRE` | Patron avec les droits de gestion, hors invitation d’un autre patron. |
| `EMPLOYE` | Opérations quotidiennes. Ne peut jamais modifier un prix, valider un arrivage, ni annuler une vente. |

Les permissions sont contrôlées côté serveur (Gates + middleware). Le frontend n’est jamais la source de vérité.

## Hébergement LWS

- Pointer le document root Apache vers le dossier `public/`
- PHP 8.3+, MySQL, `mod_rewrite`
- Copier `.env` sur le serveur, générer `APP_KEY`, renseigner les variables `DB_*` de production
- Lancer `php artisan migrate --seed`, puis vérifier les données, les relations, les transactions et les contraintes
- Compiler le frontend en local (`npm run build`) puis déployer `public/build`
- Laisser `TRUSTED_PROXIES` vide si le site est joint directement, ou y indiquer l’adresse du proxy. Ne pas y écrire une URL de tunnel.

## Tests

```bash
php artisan test
npm run build
```

## Prochaine étape

Phase 2 : articles, boutique, dépôt et stock.
