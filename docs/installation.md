# Installation Guide

## Prerequisites

- PteroCA v0.6.0 or higher installed and running
- PHP 8.2 or higher
- MySQL or MariaDB database
- SSH access to your server
- A Cloudflare account (free tier works)

## Installation Methods

### Method 1: Upload via Admin Panel (Recommended)

1. Download `subdomains.zip` from the [GitHub Releases](https://github.com/chemanc/PteroCA-Subdomains/releases) page
2. Log in to PteroCA as an administrator
3. Navigate to **Admin Panel > Plugins > Upload Plugin**
4. Upload the ZIP file (max 50MB)
5. PteroCA will automatically validate, scan, and extract the plugin
6. Enable the plugin:

```bash
cd /var/www/pteroca
php bin/console pteroca:plugin:enable subdomains
```

### Method 2: Manual File Placement

```bash
# Clone the repository
cd /tmp
git clone https://github.com/chemanc/PteroCA-Subdomains.git

# Copy the plugin folder to PteroCA's plugins directory
cp -r /tmp/PteroCA-Subdomains/subdomains/ /var/www/pteroca/plugins/subdomains/

# Set permissions
chown -R www-data:www-data /var/www/pteroca/plugins/subdomains/

# Scan for new plugins
cd /var/www/pteroca
php bin/console pteroca:plugin:scan

# Enable the plugin (runs migrations automatically)
php bin/console pteroca:plugin:enable subdomains

# Clean up
rm -rf /tmp/PteroCA-Subdomains
```

## What Happens During Enablement

When you enable the plugin, PteroCA automatically:

1. Validates the plugin manifest (`plugin.json`)
2. Runs a security scan
3. Executes database migrations (creates 4 tables + inserts default settings)
4. Publishes assets to `/public/assets/plugins/subdomains/`
5. Registers services, controllers, entities, and event subscribers
6. Calls `Bootstrap::initialize()`

## Post-Installation Configuration

1. Go to **Admin > Settings > Plugins**
2. Find the "Subdomain Manager" section
3. Enter your **Cloudflare API Token** (see [Cloudflare Setup](cloudflare-setup.md))
4. Adjust settings as needed (min/max length, cooldown, TTL, etc.)
5. Go to **Admin > Subdomains > Domains** and add your domain(s)
6. Click **Test Connection** to verify Cloudflare connectivity
7. Optionally load the default blacklist from the admin dashboard

## Verifying Installation

```bash
# Check plugin status
php bin/console pteroca:plugin:list

# Check plugin health
php bin/console pteroca:plugin:health subdomains --detailed

# Verify tables were created
php bin/console doctrine:schema:validate
```

## Updating

1. Download the new version
2. Replace the `plugins/subdomains/` folder
3. Run: `php bin/console pteroca:plugin:enable subdomains` (re-runs any new migrations)
4. Clear cache: `php bin/console cache:clear`

## Uninstalling

```bash
# Disable the plugin
php bin/console pteroca:plugin:disable subdomains

# The plugin's database tables and settings remain intact for re-enablement
# To fully remove, delete the folder:
rm -rf /var/www/pteroca/plugins/subdomains/
```

**Note:** Disabling the plugin does NOT delete database tables or user data. This is by design, so you can re-enable without losing data.
