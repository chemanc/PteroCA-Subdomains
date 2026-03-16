# PteroCA Subdomains

**Free, open-source subdomain management plugin for [PteroCA](https://pteroca.com) v0.6+.**

Allows users to create custom subdomains (e.g., `myserver.yourdomain.com`) for their game servers using Cloudflare DNS API. Designed primarily for Minecraft servers with A + SRV records, but extensible to other games.

---

## Features

- **One subdomain per server** — Each server gets its own custom subdomain
- **Cloudflare DNS integration** — Automatic A + SRV record management
- **Minecraft-optimized** — SRV records let players connect without specifying a port
- **Real-time availability check** — AJAX-powered instant feedback
- **Admin dashboard** — Statistics, domain management, bulk operations
- **EasyAdmin CRUD** — Blacklist and activity logs managed via PteroCA's admin panel
- **Multi-domain support** — Configure multiple domains with different Cloudflare zones
- **Server lifecycle automation** — Auto-suspend/delete DNS on server state changes
- **Blacklist system** — Block reserved/inappropriate subdomains (~80 default words)
- **Cooldown system** — Configurable wait period between subdomain changes
- **Bilingual** — English and Spanish translations
- **Copy-to-clipboard** — One-click copy of server address for players
- **CSV export** — Export all subdomains for reporting
- **Plugin settings UI** — All settings managed via Admin > Settings > Plugins (auto-generated)

## Requirements

- **PteroCA** v0.6.0 or higher
- **PHP 8.2+**
- **MySQL / MariaDB**
- **Cloudflare account** with API token (`Zone:DNS:Edit` permission)

---

## Installation

### Option 1: Upload via Admin Panel (Recommended)

1. Download `subdomains.zip` from [Releases](https://github.com/chemanc/PteroCA-Subdomains/releases)
2. Go to **Admin Panel > Plugins > Upload Plugin**
3. Upload the ZIP file
4. Enable the plugin:
```bash
php bin/console pteroca:plugin:enable subdomains
```

### Option 2: Manual Installation

```bash
# Clone into the plugins directory
cd /var/www/pteroca/plugins
git clone https://github.com/chemanc/PteroCA-Subdomains.git subdomains-repo
cp -r subdomains-repo/subdomains/ ./subdomains/
rm -rf subdomains-repo

# Enable the plugin
php bin/console pteroca:plugin:enable subdomains
```

### Option 3: Direct Copy

```bash
# Copy the subdomains/ folder to your PteroCA plugins directory
cp -r subdomains/ /var/www/pteroca/plugins/subdomains/

# Scan and enable
php bin/console pteroca:plugin:scan
php bin/console pteroca:plugin:enable subdomains
```

### Post-Installation

1. Go to **Admin > Settings > Plugins** and configure your Cloudflare API token
2. Go to **Admin > Subdomains > Domains** and add your domain with its Cloudflare Zone ID
3. Click **Test Connection** to verify
4. Go to **Admin > Subdomain Blacklist** and load the default blacklist (optional)

---

## How It Works

When a user creates subdomain `myserver` for a server at `144.126.138.69:25565`:

```
A Record:    myserver.thegamedimension.com  →  144.126.138.69
SRV Record:  _minecraft._tcp.myserver.thegamedimension.com  →  myserver.thegamedimension.com:25565
```

Players connect using just: **`myserver.thegamedimension.com`** (no port needed!)

---

## Plugin Structure

```
subdomains/
├── plugin.json                          # Plugin manifest & settings schema
├── Bootstrap.php                        # Plugin initialization
├── composer.json                        # PHP dependencies (PSR-4 autoload)
├── Migrations/                          # 5 Doctrine migrations
│   ├── Version20240101000001.php       #   plg_sub_domains table
│   ├── Version20240101000002.php       #   plg_sub_subdomains table
│   ├── Version20240101000003.php       #   plg_sub_blacklist table
│   ├── Version20240101000004.php       #   plg_sub_logs table
│   └── Version20240101000005.php       #   Default settings
├── src/
│   ├── Controller/
│   │   ├── SubdomainController.php     # Client: show/create/update/delete + AJAX check
│   │   └── Admin/
│   │       ├── SubdomainAdminController.php  # Admin: dashboard, domains, sync, export
│   │       ├── BlacklistCrudController.php   # EasyAdmin CRUD for blacklist
│   │       └── LogCrudController.php         # EasyAdmin CRUD for activity logs
│   ├── Entity/                          # 4 Doctrine ORM entities
│   │   ├── Subdomain.php               # Main entity (server_id, user_id, DNS IDs, status)
│   │   ├── SubdomainDomain.php         # Configured domains + Cloudflare zones
│   │   ├── SubdomainBlacklist.php      # Blocked subdomain words
│   │   ├── SubdomainLog.php            # Activity audit trail
│   │   └── Repository/                 # 4 Doctrine repositories with custom queries
│   ├── Service/
│   │   └── CloudflareService.php       # Cloudflare API v4 client (A + SRV records)
│   ├── EventSubscriber/
│   │   ├── MenuEventSubscriber.php     # Adds admin menu items
│   │   └── ServerEventSubscriber.php   # Server lifecycle (auto-suspend/delete DNS)
│   └── Exception/
│       └── CloudflareException.php
├── Resources/config/services.yaml       # Symfony DI service registration
├── templates/                           # Twig templates
│   ├── admin/
│   │   ├── dashboard.html.twig         # Stats, recent subdomains, quick actions
│   │   └── domains.html.twig           # Domain CRUD + test connection
│   └── client/
│       └── manage.html.twig            # Create/edit/delete subdomain + live preview
├── translations/
│   ├── plugin_subdomains.en.yaml       # English
│   └── plugin_subdomains.es.yaml       # Spanish
└── assets/
    ├── css/subdomains.css
    └── js/subdomains.js                # AJAX availability check, copy-to-clipboard
```

## Database Tables

| Table | Prefix | Purpose |
|-------|--------|---------|
| `plg_sub_domains` | `plg_sub_` | Configured domains with Cloudflare Zone IDs |
| `plg_sub_subdomains` | `plg_sub_` | User subdomains (1 per server) with DNS record IDs |
| `plg_sub_blacklist` | `plg_sub_` | Blocked subdomain words |
| `plg_sub_logs` | `plg_sub_` | Activity audit trail |

Settings are stored in PteroCA's `setting` table with context `plugin:subdomains`.

## Plugin Settings

Configured via **Admin > Settings > Plugins** (auto-generated UI from `plugin.json`):

| Setting | Default | Description |
|---------|---------|-------------|
| Cloudflare API Token | — | API token with Zone:DNS:Edit permissions |
| Minimum Length | 3 | Minimum subdomain characters |
| Maximum Length | 32 | Maximum subdomain characters |
| Change Cooldown | 24h | Hours between subdomain changes |
| Default TTL | Auto | DNS record time-to-live |
| Auto-delete on Termination | Yes | Delete DNS when server is terminated |
| Auto-suspend on Suspension | Yes | Disable DNS when server is suspended |
| Rate Limit | 5/min | API requests per minute per user |

---

## Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Cloudflare Setup](docs/cloudflare-setup.md)
- [Troubleshooting](docs/troubleshooting.md)

## CLI Commands

```bash
# List all plugins
php bin/console pteroca:plugin:list

# Enable the plugin
php bin/console pteroca:plugin:enable subdomains

# Disable the plugin
php bin/console pteroca:plugin:disable subdomains

# Check plugin health
php bin/console pteroca:plugin:health subdomains --detailed

# Security scan
php bin/console pteroca:plugin:security-scan subdomains
```

## License

MIT License — see [LICENSE](LICENSE) for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- Built for [PteroCA](https://pteroca.com) v0.6+
- DNS management via [Cloudflare API v4](https://developers.cloudflare.com/api/)
- Plugin architecture based on PteroCA's native plugin system (Symfony 7 + Doctrine ORM + EasyAdmin)
