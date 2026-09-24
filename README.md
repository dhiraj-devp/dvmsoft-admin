# Dvmsoft Admin OS

Internal operating system for Dvmsoft. This foundation covers authentication, RBAC, settings, audit logging, and the administration UI. Business modules (CRM, projects, finance, HR, support, documents, and the client portal) are intentionally not implemented yet.

## Requirements

- PHP 8.3+ (8.4 recommended)
- Composer 2
- Node.js 20+ and npm
- MySQL 8+ or MariaDB 10.4+
- Optional: Redis for cache/queues in production

## Installation

```bash
git clone <repository-url> dvmsoft-admin
cd dvmsoft-admin
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## MySQL setup

Create the database before migrating:

```sql
CREATE DATABASE dvmsoft_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## .env configuration

Set these values in `.env`. Never commit real passwords.

```ini
APP_NAME="Dvmsoft Admin OS"
APP_URL=http://127.0.0.1:8000
ADMIN_URL=http://127.0.0.1:8000
CLIENT_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dvmsoft_admin
DB_USERNAME=root
DB_PASSWORD=

ADMIN_NAME="Super Admin"
ADMIN_EMAIL=admin@dvmsoft.local
ADMIN_PASSWORD=change-me
```

`ADMIN_*` values are used only by the development seeder to create the first Super Admin.

Keep `SESSION_DOMAIN` empty so authentication cookies stay host-only and are not shared between the staff and client hosts.

## Local URLs

Default local development uses `127.0.0.1` (same as `php artisan serve`):

| App | Production | Local (default) |
|---|---|---|
| Staff / Admin OS | `https://admin.dvmsoft.com/login` | `http://127.0.0.1:8000/login` |
| Client Portal | `https://client.dvmsoft.com/login` | `http://127.0.0.1:8000/client/login` |

When `ADMIN_URL` and `CLIENT_URL` share that origin, client routes stay under `/client`.

Optional dedicated local hosts (Herd or hosts file):

| App | Local (optional) |
|---|---|
| Staff / Admin OS | `http://admin.dvmsoft.test/login` |
| Client Portal | `http://client.dvmsoft.test/login` |

`127.0.0.1` and `localhost` always serve the staff app, even if dedicated hosts are configured. They are not redirected away.

## Migration commands

```bash
php artisan migrate
```

Reset local data:

```bash
php artisan migrate:fresh
```

## Seeder commands

```bash
php artisan db:seed
```

Or together:

```bash
php artisan migrate:fresh --seed
```

This creates departments, roles, permissions, default settings, and the Super Admin user.

## Storage link

Required for company logo and favicon uploads:

```bash
php artisan storage:link
```

## Development commands

```bash
php artisan serve
npm run dev
php artisan queue:work
```

Or run the bundled Composer script:

```bash
composer run dev
```

Default login after seeding (from `.env`):

- Staff: `http://127.0.0.1:8000/login`
- Client Portal: `http://127.0.0.1:8000/client/login`
- Seeded Super Admin uses `ADMIN_EMAIL` / `ADMIN_PASSWORD` through a dedicated internal operator sign-in, not the staff login page.

## Build commands

```bash
npm run build
vendor/bin/pint
php artisan test
```

## Production deployment notes

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Use a strong `APP_KEY` and unique `ADMIN_PASSWORD` only for the initial seed, then rotate it
- Enable `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true` behind HTTPS
- Set `APP_URL` and `ADMIN_URL` to `https://admin.dvmsoft.com` and `CLIENT_URL` to `https://client.dvmsoft.com`
- Point both hostnames at this application; do not set `SESSION_DOMAIN` to a parent domain such as `.dvmsoft.com`
- Point `QUEUE_CONNECTION` and `CACHE_STORE` at Redis when available
- Run `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, and `npm run build`
- Serve the `public` directory only
- Configure a process manager for `queue:work` and scheduled tasks (`php artisan schedule:work` or cron `* * * * * php artisan schedule:run`)
- Restrict file uploads to the public disk branding path and keep backups of MySQL

## Troubleshooting

If `php artisan migrate:fresh` fails with a tablespace error for `migrations`, MariaDB left an orphaned `migrations.ibd` file. Either remove that leftover file from the MySQL data directory or temporarily set `DB_MIGRATIONS_TABLE=schema_migrations` in `.env`.

## Architecture notes

- All employees share staff login on the admin host. Access is permission-based, not role-name-based.
- Super Admin unrestricted access is granted through `users.is_super_admin` plus a Super Admin role that receives every permission. Super Admin is a staff user on the same `web` guard, with a dedicated unpublished operator sign-in that is not linked from staff or client UI.
- Client Portal users authenticate on the client host with the `client` guard and `client_users` table.
- Read settings with `settings('company.name')`.
- Navigation is defined in `config/navigation.php` and filtered by permissions. Unbuilt modules stay `enabled => false`.
- Future modules should add permissions, policies, navigation entries, and migrations without rewriting this foundation.
