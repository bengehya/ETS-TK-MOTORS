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
- Copier `.env` sur le serveur, générer `APP_KEY`, lancer `php artisan migrate --seed`
- Compiler le frontend en local (`npm run build`) puis déployer `public/build`

## Tests

```bash
php artisan test
npm run build
```

## Prochaine étape

Phase 2 : articles, boutique, dépôt et stock.
