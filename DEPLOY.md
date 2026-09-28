# Deploying to Forge

The app runs at https://fiestafieldguide.com on the shared Forge server
`159.203.159.108`, alongside the other pet projects and a client site. DNS is at
Porkbun: an `A` record on the root pointing at the server, and `www` as a `CNAME` to the
root.

The app needs PHP 8.4, because the lock file resolves to Symfony 8, and `pdo_sqlite`. The
Vite build needs Node 20.19 or newer. The server has all three. Every other site on the
server stays on its own PHP version.

## 1. Site

Created in Forge with these settings. Website isolation, zero downtime and the build
command are under **Advanced settings** in the new-site form.

- **Domain:** `fiestafieldguide.com`, with `www.fiestafieldguide.com` as an alias
- **Repository:** `ccennis/fiesta-field-guide`, branch `main`
- **Web directory:** `/public`
- **PHP version:** 8.4
- **Website isolation:** on, user `fiesta`. Isolation only matters if other sites' files
  are not world readable, so keep every `.env` on the server at `640`.
- **Zero-downtime deployments:** on, with the default `storage` shared path
- **Push to deploy:** on, so every merge to `main` deploys
- **Database:** none. The app uses SQLite.
- **SSL:** Let's Encrypt for both domains, issued once DNS pointed at the server

Server commands run as the isolated user, which accepts the same SSH keys as `forge`:

```bash
ssh fiesta@159.203.159.108
```

## 2. Environment

Forge keeps the `APP_KEY` it generated. Every other `DB_` line it creates is removed.

```dotenv
APP_NAME="Fiesta Field Guide"
APP_ENV=production
APP_KEY=base64:keep-the-one-forge-generated
APP_DEBUG=false
APP_URL=https://fiestafieldguide.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=/home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

The database path points into the shared `storage` folder. Zero-downtime deploys build
every release in a fresh folder, and only `storage` and `.env` carry across. A database
left at the default `database/database.sqlite` would start empty on every deploy.

`QUEUE_CONNECTION=sync` is deliberate. Nothing in the app is queued, so no worker or
Horizon process is needed.

## 3. Deploy script

Forge's default zero-downtime script, with the database file created before migrating.

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci || npm install
npm run build

# The SQLite database lives in shared storage so every release uses the same file.
mkdir -p /home/fiesta/fiestafieldguide.com/storage/database
touch /home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

## 4. Data and login

The production database was first filled by uploading the local one, which carried the
login, the wishlist and anything recorded locally. To do that again, make a consistent
copy with `.backup` rather than copying the file, upload it next to the live file, then
swap it in.

```bash
sqlite3 database/database.sqlite ".backup 'fiesta-upload.sqlite'"
```

```bash
scp fiesta-upload.sqlite fiesta@159.203.159.108:/home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite.new
```

```bash
ssh fiesta@159.203.159.108 "mv /home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite.new /home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite"
```

To start from the seed data instead, run this from the current release, then create the
login:

```bash
ssh fiesta@159.203.159.108 "cd /home/fiesta/fiestafieldguide.com/current && php8.4 artisan db:seed --force"
```

The owner's login is created, or its password reset, interactively on the server:

```bash
ssh -t fiesta@159.203.159.108 "cd /home/fiesta/fiestafieldguide.com/current && php8.4 artisan fiesta:make-user you@example.com"
```

Beta testers are invited from the Testers screen in the app, not from the server. Invite
links are built from `APP_URL`, so it must be the public address.

The migration that added roles made the oldest existing login the owner, and gave it
every piece and wishlist item already in the database.

## 5. Nightly backup

Forge's database backups cover MySQL and Postgres only, so SQLite needs its own. A
scheduled job in Forge runs nightly as the `fiesta` user:

```bash
mkdir -p /home/fiesta/backups && sqlite3 /home/fiesta/fiestafieldguide.com/storage/database/fiesta.sqlite ".backup '/home/fiesta/backups/fiesta-$(date +\%F).sqlite'" && find /home/fiesta/backups -name 'fiesta-*.sqlite' -mtime +14 -delete
```

This keeps two weeks of copies. The `\%` is required because cron treats a bare `%` as a
line break. SQLite's `.backup` is safe to run while the app is serving requests, unlike a
plain file copy.

These copies live on the same server, so they protect against mistakes but not against
losing the server. DigitalOcean droplet backups, or a copy to Spaces, would cover that.

## 6. Store import

`fiesta:import-ffd` reads the Fiesta Factory Direct catalog every Monday at 6:00, and
`fiesta:suggest-swatches` samples store photos at 6:30, through Laravel's scheduler. The scheduler only runs if Forge calls it, so the site's
**Scheduler** needs to be enabled in Forge, running `php artisan schedule:run` every
minute as the `fiesta` user.

Rulings on the store's names are made interactively on the server:

```bash
ssh -t fiesta@159.203.159.108 "cd /home/fiesta/fiestafieldguide.com/current && php8.4 artisan fiesta:rule-listings"
```
