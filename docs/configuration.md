# Configuration

## Admin Panel Settings

All settings are managed through **Admin > Subdomains > Settings**.

### Cloudflare Settings

| Setting | Description |
|---------|-------------|
| **API Token** | Your Cloudflare API token (stored encrypted in database) |

### Subdomain Settings

| Setting | Default | Description |
|---------|---------|-------------|
| **Minimum Length** | 3 | Minimum characters for a subdomain |
| **Maximum Length** | 32 | Maximum characters for a subdomain |
| **Change Cooldown** | 24 hours | Time users must wait before changing their subdomain. Set to 0 to disable |
| **Default TTL** | Auto | DNS record time-to-live. Options: Auto, 1 min, 5 min, 30 min, 1 hour, 12 hours, 1 day |
| **Auto-delete on Termination** | Enabled | Automatically remove DNS records when a server is terminated |
| **Auto-suspend on Suspension** | Enabled | Automatically disable DNS records when a server is suspended |

### Domain Management

You can configure multiple domains. Each domain requires:

| Field | Description |
|-------|-------------|
| **Domain Name** | The root domain (e.g., `thegamedimension.com`) |
| **Cloudflare Zone ID** | The Zone ID from Cloudflare (see [Cloudflare Setup](cloudflare-setup.md)) |
| **Default** | Whether this is the default domain for new subdomains |

## Database Settings Table

Settings are stored in `pteroca_subdomain_settings`:

| Key | Default | Description |
|-----|---------|-------------|
| `cloudflare_api_token` | `null` | Encrypted Cloudflare API token |
| `min_length` | `3` | Minimum subdomain length |
| `max_length` | `32` | Maximum subdomain length |
| `change_cooldown_hours` | `24` | Cooldown period in hours |
| `auto_delete_on_terminate` | `true` | Auto-delete DNS on server termination |
| `auto_suspend_on_suspend` | `true` | Auto-suspend DNS on server suspension |
| `default_ttl` | `1` | TTL in seconds (1 = Auto) |

## Environment Variables

Optional `.env` configuration:

```env
# Master switch to enable/disable the subdomain feature
SUBDOMAIN_ENABLED=true
```

## Config File

The config file `config/subdomains.php` provides defaults that are used when database settings are not yet configured. Database settings always take precedence.

## Rate Limiting

The API endpoint for availability checks is rate-limited to **5 requests per minute per user**. This is configured in the `rate_limit` config key and can be adjusted in `config/subdomains.php`.

## Blacklist

The blacklist blocks specific subdomain names. You can:

1. **Manually add** words through the admin panel
2. **Import** a text file (one word per line)
3. **Load defaults** — ~80 common reserved words (admin, www, api, etc.)
4. **Export** the current blacklist as a text file

The blacklist checks both exact matches and substring containment.
