# PteroCA Subdomains

**Free, open-source subdomain management plugin for [PteroCA](https://pteroca.com) v0.6+.**

Allows users to create custom subdomains (e.g., `myserver.yourdomain.com`) for their game servers using Cloudflare DNS API. Designed primarily for Minecraft servers with A + SRV records, but extensible to other games.

---

## Features

- **One subdomain per server** — Each server gets its own custom subdomain
- **Server tab integration** — "Subdomain" tab directly in the server management page
- **Address replacement** — Subdomain replaces IP in the console "DIRECCIÓN" card
- **Cloudflare DNS integration** — Automatic A + SRV record management (updated API format)
- **Minecraft-optimized** — SRV records let players connect without specifying a port
- **Real-time availability check** — AJAX-powered instant feedback
- **Admin dashboard** — Statistics, domain management, bulk operations (collapsible admin menu)
- **EasyAdmin CRUD** — Blacklist and activity logs managed via PteroCA's admin panel
- **Multi-domain support** — Configure multiple domains with different Cloudflare zones
- **Server lifecycle automation** — Auto-suspend/delete DNS on server state changes
- **Blacklist system** — Block reserved/inappropriate subdomains (~80 default words)
- **Cooldown system** — Configurable wait period between subdomain changes
- **Bilingual** — English and Spanish translations
- **Copy-to-clipboard** — One-click copy of server address for players
- **CSV export** — Export all subdomains for reporting
- **Plugin settings UI** — All settings managed via Admin > Settings > Plugins (auto-generated)
- **Security hardened** — CSRF protection, ROLE_ADMIN enforcement, XSS prevention, input validation

## Requirements

- **PteroCA** v0.6.0 or higher
- **PHP 8.2+**
- **MySQL / MariaDB**
- **Cloudflare account** with API token (`Zone:DNS:Edit` permission)

---

## Installation

### Option 1: Upload via Admin Panel (Recommended)

1. Download `subdomains.zip` from [Releases](https://github.com/chemanc/PteroCA-Subdomains/releases/latest)
2. Go to **Admin Panel > Plugins > Upload Plugin**
3. Upload the ZIP file — the plugin will be enabled automatically

### Option 2: Manual Installation

```bash
cd /var/www/pteroca/plugins
git clone https://github.com/chemanc/PteroCA-Subdomains.git /tmp/ptero-sub
cp -r /tmp/ptero-sub/subdomains/ ./subdomains/
chown -R www-data:www-data ./subdomains/
rm -rf /tmp/ptero-sub

php bin/console pteroca:plugin:scan
php bin/console pteroca:plugin:enable subdomains
php bin/console cache:clear
```

### Post-Installation

1. Go to **Admin > Plugins > Subdomains** (gear icon) and configure your **Cloudflare API Token**
2. Go to **Admin > Subdomains > DNS Domains** and add your domain with its Cloudflare Zone ID
3. Click **Test Connection** to verify
4. Optionally load the default blacklist from **Admin > Subdomains > Dashboard > Quick Actions**

---

## How It Works

When a user creates subdomain `myserver` for a server at `144.126.138.69:25565`:

```
A Record:    myserver.yourdomain.com  →  144.126.138.69
SRV Record:  _minecraft._tcp.myserver.yourdomain.com  →  myserver.yourdomain.com:25565
```

Players connect using just: **`myserver.yourdomain.com`** (no port needed!)

The subdomain is managed from a dedicated **"Subdomain" tab** in the server management page, and the console "DIRECCIÓN" card automatically shows the subdomain instead of the raw IP.

---

## Plugin Structure

```
subdomains/
├── plugin.json                          # Plugin manifest & settings schema
├── Bootstrap.php                        # Plugin initialization
├── Migrations/                          # Doctrine migrations (4 tables)
├── src/
│   ├── Controller/
│   │   ├── SubdomainController.php     # Client: show/create/update/delete + AJAX check
│   │   └── Admin/
│   │       ├── SubdomainCrudController.php  # Admin dashboard (EasyAdmin)
│   │       ├── DomainCrudController.php     # Domain management (EasyAdmin)
│   │       ├── SubdomainApiController.php   # Admin API: sync, export, domains CRUD
│   │       ├── BlacklistCrudController.php  # EasyAdmin CRUD for blacklist
│   │       └── LogCrudController.php        # EasyAdmin CRUD for activity logs
│   ├── Tab/
│   │   └── SubdomainTab.php            # Server tab integration
│   ├── Entity/                          # 4 Doctrine ORM entities + repositories
│   ├── Service/
│   │   └── CloudflareService.php       # Cloudflare API v4 (post-2024 SRV format)
│   ├── EventSubscriber/
│   │   ├── MenuEventSubscriber.php     # Collapsible admin menu
│   │   └── ServerEventSubscriber.php   # Server lifecycle automation
│   └── Exception/
│       └── CloudflareException.php
├── Resources/config/services.yaml       # Service registration
├── templates/
│   ├── admin/                          # Dashboard + domains (EasyAdmin layout)
│   ├── client/                         # Server tab + manage partial
│   └── components/footer.html.twig     # Shared footer (DRY)
├── translations/                        # EN + ES
└── assets/                             # CSS + JS
```

## Security

- **CSRF protection** on all state-changing forms
- **ROLE_ADMIN** required for all admin endpoints
- **Authentication** required for all client endpoints
- **Input validation** — subdomain format, domain format, Zone ID format
- **XSS prevention** — HTML escaping on all dynamic JS output
- **Generic error messages** — no internal details exposed to users
- **Blacklist** — prevents registration of reserved/offensive subdomains

## Plugin Settings

Configured via **Admin > Plugins > Subdomains** (gear icon):

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

## Uninstall

```bash
cd /var/www/pteroca
php bin/console pteroca:plugin:disable subdomains
rm -rf plugins/subdomains/

# Optional: remove database tables
php bin/console dbal:run-sql "DROP TABLE IF EXISTS plg_sub_logs, plg_sub_subdomains, plg_sub_blacklist, plg_sub_domains"
php bin/console dbal:run-sql "DELETE FROM setting WHERE context = 'plugin:subdomains'"
php bin/console dbal:run-sql "DELETE FROM plugin WHERE name = 'subdomains'"
php bin/console cache:clear
```

## Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Cloudflare Setup](docs/cloudflare-setup.md)
- [Troubleshooting](docs/troubleshooting.md)

## License

MIT License — see [LICENSE](LICENSE) for details.

## Credits

- Built for [PteroCA](https://pteroca.com) v0.6+
- DNS management via [Cloudflare API v4](https://developers.cloudflare.com/api/)
- Developed by [XMA Corporation](https://buymeacoffee.com/chemanc)

---

<p align="center">
  <a href="https://buymeacoffee.com/chemanc"><img src="https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-yellow?style=for-the-badge&logo=buy-me-a-coffee" alt="Buy Me A Coffee"></a>
</p>
