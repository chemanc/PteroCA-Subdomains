# Configuration

## Plugin Settings

All settings are managed through **Admin > Settings > Plugins** (auto-generated UI from `plugin.json`).

### General Settings

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| **Cloudflare API Token** | password | — | Your Cloudflare API token (encrypted automatically) |
| **Minimum Length** | integer | 3 | Minimum characters for a subdomain |
| **Maximum Length** | integer | 32 | Maximum characters for a subdomain |
| **Change Cooldown** | integer | 24 | Hours users must wait before changing their subdomain. 0 = disabled |
| **Default TTL** | select | Auto | DNS record TTL. Options: Auto, 1 min, 5 min, 30 min, 1 hour, 12 hours, 1 day |

### Advanced Settings

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| **Auto-delete on Termination** | boolean | true | Delete DNS records when a server is terminated |
| **Auto-suspend on Suspension** | boolean | true | Disable DNS records when a server is suspended |
| **Rate Limit** | integer | 5 | Maximum API requests per minute per user |

## Settings Storage

Settings are stored in PteroCA's `setting` table with context `plugin:subdomains`. They are accessed via `PluginSettingService`:

```php
$this->pluginSettingService->get('subdomains', 'cloudflare_api_token', '');
$this->pluginSettingService->get('subdomains', 'min_length', 3);
```

Password-type settings (like `cloudflare_api_token`) are automatically encrypted by PteroCA.

## Domain Management

Domains are configured via **Admin > Subdomains > Domains** (or the admin dashboard). Each domain requires:

| Field | Description |
|-------|-------------|
| **Domain Name** | The root domain (e.g., `thegamedimension.com`) |
| **Cloudflare Zone ID** | The Zone ID from Cloudflare |
| **Default** | Whether this is the default domain for new subdomains |

## Blacklist

The blacklist is managed via **Admin > Subdomain Blacklist** (EasyAdmin CRUD). You can:

- Add/remove individual words
- Load ~80 default reserved words (admin, www, api, minecraft, etc.)
- Search through the blacklist

The blacklist checks both exact matches and substring containment.

## Activity Logs

All subdomain operations are logged and viewable via **Admin > Subdomain Logs** (EasyAdmin CRUD). Logs include:

- Action type (create, update, delete, suspend, unsuspend, error)
- User ID
- IP address
- Detailed JSON data
- Timestamp
