# PteroCA Subdomains

**Free, open-source subdomain management plugin for [PteroCA](https://pteroca.com).**

Allows users to create custom subdomains (e.g., `myserver.yourdomain.com`) for their game servers using Cloudflare DNS API. Designed primarily for Minecraft servers with A + SRV records, but extensible to other games.

---

## Features

- **One subdomain per server** — Each server gets its own custom subdomain
- **Cloudflare DNS integration** — Automatic A + SRV record management
- **Minecraft-optimized** — SRV records let players connect without specifying a port
- **Real-time availability check** — AJAX-powered instant feedback
- **Admin dashboard** — Statistics, settings, blacklist, activity logs
- **Multi-domain support** — Configure multiple domains with different Cloudflare zones
- **Server lifecycle automation** — Auto-suspend/delete DNS on server state changes
- **Blacklist system** — Block reserved/inappropriate subdomains with default list (~80 words)
- **Cooldown system** — Configurable wait period between subdomain changes
- **Rate limiting** — Configurable API request limits per user
- **Bilingual** — English and Spanish translations included (231 translation keys)
- **Audit trail** — Complete activity logging with IP tracking
- **Copy-to-clipboard** — One-click copy of server address for players
- **CSV export** — Export all subdomains for reporting

## Requirements

- **PteroCA** (Laravel-based Pterodactyl billing panel)
- **PHP 8.1+**
- **MySQL / MariaDB**
- **Cloudflare account** with API token (`Zone:DNS:Edit` permission)

---

## Quick Install

> For detailed instructions, see [docs/installation.md](docs/installation.md).

### 1. Download

```bash
cd /tmp
git clone https://github.com/chemanc/PteroCA-Subdomains.git
```

### 2. Copy plugin files to PteroCA

```bash
PTEROCA=/var/www/pteroca
PLUGIN=/tmp/PteroCA-Subdomains

cp -r $PLUGIN/src/app/* $PTEROCA/app/
cp $PLUGIN/src/config/subdomains.php $PTEROCA/config/
cp -r $PLUGIN/src/database/* $PTEROCA/database/
cp -r $PLUGIN/src/resources/* $PTEROCA/resources/
cp $PLUGIN/src/routes/subdomains.php $PTEROCA/routes/
```

### 3. Register the ServiceProvider

Edit `config/app.php` and add to the `providers` array:

```php
App\Providers\SubdomainServiceProvider::class,
```

### 4. Run migrations

```bash
cd /var/www/pteroca
php artisan migrate
```

### 5. Clear cache

```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
```

### 6. Configure

1. Log in as admin
2. Go to **Admin > Subdomains > Settings**
3. Enter your Cloudflare API token
4. Add your domain with its Zone ID
5. Click **Test Connection**
6. Go to **Blacklist** > **Load Default Blacklist**

---

## How It Works

When a user creates subdomain `myserver` for a server at `144.126.138.69:25565`:

```
A Record:    myserver.thegamedimension.com  →  144.126.138.69
SRV Record:  _minecraft._tcp.myserver.thegamedimension.com  →  myserver.thegamedimension.com:25565
```

Players connect using just: **`myserver.thegamedimension.com`** (no port needed!)

---

## Project Structure

```
PteroCA-Subdomains/
├── src/                                    # Plugin source code
│   ├── app/
│   │   ├── Exceptions/
│   │   │   └── CloudflareException.php     # Custom exception for CF API errors
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/
│   │   │   │   │   └── SubdomainController.php   # Admin panel (16 endpoints)
│   │   │   │   └── Client/
│   │   │   │       └── SubdomainController.php   # User panel (5 endpoints)
│   │   │   ├── Middleware/
│   │   │   │   └── SubdomainRateLimit.php        # Rate limiting per user
│   │   │   └── Requests/
│   │   │       └── SubdomainRequest.php          # Form request validation
│   │   ├── Models/
│   │   │   ├── Subdomain.php              # Main model + settings helper
│   │   │   ├── SubdomainDomain.php        # Domain configuration
│   │   │   ├── SubdomainBlacklist.php     # Blocked words
│   │   │   └── SubdomainLog.php           # Activity audit log
│   │   ├── Observers/
│   │   │   └── ServerObserver.php         # Server lifecycle hooks
│   │   ├── Providers/
│   │   │   └── SubdomainServiceProvider.php
│   │   ├── Rules/
│   │   │   ├── NotBlacklisted.php         # Blacklist validation rule
│   │   │   └── SubdomainAvailable.php     # Uniqueness validation rule
│   │   └── Services/
│   │       └── CloudflareService.php      # Cloudflare API v4 client
│   ├── config/
│   │   └── subdomains.php                 # Default configuration
│   ├── database/
│   │   └── migrations/
│   │       └── 2024_01_01_000000_create_subdomain_tables.php
│   ├── resources/
│   │   ├── views/
│   │   │   ├── admin/subdomains/          # 4 admin views
│   │   │   └── client/subdomains/         # 1 client view
│   │   └── lang/
│   │       ├── en/subdomains.php          # English (231 keys)
│   │       └── es/subdomains.php          # Spanish (231 keys)
│   └── routes/
│       └── subdomains.php                 # All routes (admin + client + API)
├── docs/
│   ├── installation.md                    # Detailed install guide
│   ├── configuration.md                   # All settings explained
│   ├── cloudflare-setup.md                # Cloudflare token & zone setup
│   └── troubleshooting.md                 # Common issues & fixes
├── tests/
│   └── Feature/
│       └── SubdomainTest.php              # 20 feature tests
├── README.md
├── LICENSE                                # MIT
└── CHANGELOG.md
```

## Database Tables

| Table | Purpose |
|-------|---------|
| `pteroca_subdomains` | Main subdomain records (server_id, user_id, DNS record IDs, status) |
| `pteroca_subdomain_domains` | Configured domains with Cloudflare Zone IDs |
| `pteroca_subdomain_blacklist` | Blocked subdomain words |
| `pteroca_subdomain_logs` | Activity audit trail (action, user, IP, details) |
| `pteroca_subdomain_settings` | Key-value settings store |

## API Endpoints

### Admin Routes (`/admin/subdomains/...`)
Dashboard, settings CRUD, domain management, blacklist CRUD (with import/export), activity logs, DNS sync, CSV export — **16 endpoints total**.

### Client Routes
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/servers/{id}/subdomain` | View/manage subdomain |
| POST | `/servers/{id}/subdomain` | Create subdomain |
| PUT | `/servers/{id}/subdomain` | Change subdomain |
| DELETE | `/servers/{id}/subdomain` | Delete subdomain |
| POST | `/api/subdomains/check` | AJAX availability check |

---

## Documentation

- [Installation Guide](docs/installation.md) — Step-by-step setup
- [Configuration](docs/configuration.md) — All settings explained
- [Cloudflare Setup](docs/cloudflare-setup.md) — API token creation guide
- [Troubleshooting](docs/troubleshooting.md) — Common issues and fixes

## License

MIT License — see [LICENSE](LICENSE) for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- Built for [PteroCA](https://pteroca.com)
- DNS management via [Cloudflare API v4](https://developers.cloudflare.com/api/)
