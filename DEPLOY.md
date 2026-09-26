# Deploying to Forge

The app runs on the shared Forge server alongside the other pet projects. It needs PHP 8.3
or newer, `pdo_sqlite`, and Node 20.19 or newer for the Vite build. The server has all
three.

Replace `fiesta.example.com` below with the real domain, and `fiesta` with the isolated
user name chosen when the site is created.

## 1. Create the site

- **Domain:** `fiesta.example.com`
- **Project type:** Laravel
- **Web directory:** `/public`
- **PHP version:** 8.3
- **Website isolation:** on, user `fiesta`. The server also hosts a client site, so this
  site gets its own system user and cannot read the others' files.
- **Zero-downtime deployments:** on
- **Database:** none. The app uses SQLite, so Forge does not need to create one.
- Connect the repository, then issue a Let's Encrypt certificate. The app will not
  install to an iPhone home screen without HTTPS.

## 2. Environment

Replace the environment Forge generates with this, keeping the `APP_KEY` it created. Every
`DB_` line other than these two should be removed.

```dotenv
APP_NAME="Fiesta Field Guide"
APP_ENV=production
APP_KEY=base64:keep-the-one-forge-generated
APP_DEBUG=false
APP_URL=https://fiesta.example.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=/home/fiesta/fiesta.example.com/storage/database/fiesta.sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

The database path points into the site's shared `storage` folder. Zero-downtime deploys
build every release in a fresh folder, and only `storage` and `.env` are carried across. A
database left at the default `database/database.sqlite` would start empty on every deploy.

`QUEUE_CONNECTION=sync` is deliberate. Nothing in the app is queued, so no worker or
Horizon process is needed.

## 3. Deploy script

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

# The database lives in shared storage. Create the file on the first deploy only.
mkdir -p $FORGE_SITE_PATH/storage/database
touch $FORGE_SITE_PATH/storage/database/fiesta.sqlite

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize

$ACTIVATE_RELEASE()
```

Compare this with the default zero-downtime script Forge shows for the new site before
saving. The macro and variable names come from Forge and should match what it generates.

## 4. First deploy

Deploy once from Forge. Then fill the database, using one of these:

**Start from the seed data.** Imports the catalog, holdings and wishlist from
`database/seed-data/`. Anything changed locally since the import is not carried over.

```bash
php artisan db:seed --force
```

**Or bring the local database up.** Carries over everything, including grails, prices and
pieces recorded in the app. Copy it from the Mac, then deploy again so the file is owned
correctly.

```bash
scp database/database.sqlite fiesta@SERVER_IP:/home/fiesta/fiesta.example.com/storage/database/fiesta.sqlite
```

Then create the login on the server. It prompts for the password in the terminal.

```bash
php artisan fiesta:make-user you@example.com
```

Run server commands from `/home/fiesta/fiesta.example.com/current` as the `fiesta` user.

## 5. Nightly backup

Forge's database backups cover MySQL and Postgres only, so SQLite needs its own. Add a
scheduled job in Forge that runs nightly as the `fiesta` user:

```bash
mkdir -p /home/fiesta/backups && sqlite3 /home/fiesta/fiesta.example.com/storage/database/fiesta.sqlite ".backup '/home/fiesta/backups/fiesta-$(date +\%F).sqlite'" && find /home/fiesta/backups -name 'fiesta-*.sqlite' -mtime +14 -delete
```

This keeps two weeks of copies. The `\%` is required because cron treats a bare `%` as a
line break. SQLite's `.backup` is safe to run while the app is serving requests, unlike a
plain file copy.

These copies live on the same server, so they protect against mistakes but not against
losing the server. DigitalOcean droplet backups, or a copy to Spaces, would cover that.
