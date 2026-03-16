# Installation Guide

## Prerequisites

- PteroCA installed and running (typically at `/var/www/pteroca`)
- PHP 8.1 or higher
- MySQL or MariaDB database
- SSH access to your server
- A Cloudflare account (free tier works)

## Step-by-Step Installation

### 1. Download the Plugin

```bash
# Clone or download the repository
git clone https://github.com/chemanc/PteroCA-Subdomains.git /tmp/pteroca-subdomains
```

### 2. Copy Plugin Files

Copy all files from `src/` into your PteroCA installation directory:

```bash
# App files (controllers, models, services, etc.)
cp -r /tmp/pteroca-subdomains/src/app/* /var/www/pteroca/app/

# Configuration
cp /tmp/pteroca-subdomains/src/config/subdomains.php /var/www/pteroca/config/

# Database migrations
cp -r /tmp/pteroca-subdomains/src/database/* /var/www/pteroca/database/

# Views and translations
cp -r /tmp/pteroca-subdomains/src/resources/* /var/www/pteroca/resources/

# Routes
cp /tmp/pteroca-subdomains/src/routes/subdomains.php /var/www/pteroca/routes/
```

### 3. Register the ServiceProvider

Edit `/var/www/pteroca/config/app.php` and add the ServiceProvider to the `providers` array:

```php
'providers' => [
    // ... existing providers ...
    App\Providers\SubdomainServiceProvider::class,
],
```

### 4. Run Database Migrations

```bash
cd /var/www/pteroca
php artisan migrate
```

This creates 5 tables:
- `pteroca_subdomains` — Main subdomain records
- `pteroca_subdomain_domains` — Domain configuration
- `pteroca_subdomain_blacklist` — Blocked subdomains
- `pteroca_subdomain_logs` — Activity audit trail
- `pteroca_subdomain_settings` — Plugin settings (with defaults)

### 5. Set File Permissions

```bash
chown -R www-data:www-data /var/www/pteroca/app/
chown -R www-data:www-data /var/www/pteroca/resources/views/vendor/
```

### 6. Clear Cache

```bash
cd /var/www/pteroca
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 7. Configure the Plugin

1. Log in to PteroCA as an administrator
2. Navigate to **Admin > Subdomains > Settings**
3. Enter your Cloudflare API token
4. Add your domain(s) with Cloudflare Zone ID(s)
5. Click **Test Connection** to verify
6. Go to **Blacklist** and click **Load Default Blacklist**

See [configuration.md](configuration.md) and [cloudflare-setup.md](cloudflare-setup.md) for details.

## Updating

To update the plugin:

1. Back up your database
2. Download the new version
3. Copy files over (same as step 2 above)
4. Run `php artisan migrate` for any new migrations
5. Clear cache

## Uninstalling

1. Remove the ServiceProvider from `config/app.php`
2. Run: `php artisan migrate:rollback` (rolls back the subdomain tables)
3. Delete the plugin files from the directories listed in step 2
4. Clear cache
