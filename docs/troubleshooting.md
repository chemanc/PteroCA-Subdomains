# Troubleshooting

## Plugin Won't Enable

**Check health:**
```bash
php bin/console pteroca:plugin:health subdomains --detailed
```

**Check security scan:**
```bash
php bin/console pteroca:plugin:security-scan subdomains --detailed
```

**Common causes:**
- Missing `plugin.json` or invalid format
- PHP version below 8.2
- PteroCA version outside the min/max range (requires 0.6.0 - 1.0.0)
- Critical security issues detected

---

## "Cloudflare API token is not configured"

Go to **Admin > Settings > Plugins** and enter your Cloudflare API token. Make sure to save the settings.

---

## "Failed to connect to Cloudflare"

1. Verify your API token has "Zone:DNS:Edit" permissions
2. Verify the Zone ID matches your domain
3. Check if the token has expired
4. Try creating a new API token
5. Use the **Test Connection** button in Admin > Subdomains > Domains

---

## DNS Not Resolving

DNS propagation can take up to 5 minutes. Verify with:

```bash
# Check A record
nslookup myserver.yourdomain.com

# Check SRV record
nslookup -type=SRV _minecraft._tcp.myserver.yourdomain.com
```

Check the subdomain status in the admin dashboard — if it shows "Error", the error message will explain what went wrong.

---

## "This subdomain is not allowed"

The subdomain matches a blacklisted word. The blacklist checks both exact matches and substrings (e.g., "admin" blocks "admin", "myadmin", "administrator").

To manage: **Admin > Subdomain Blacklist**

---

## Server Address Shows Wrong IP/Port

The plugin reads the server's IP and port from PteroCA's Server entity. If the server's allocation changed after the subdomain was created, delete and recreate the subdomain.

---

## Subdomain Not Deleted After Server Termination

1. Check that **Auto-delete on Server Termination** is enabled in Admin > Settings > Plugins
2. Check **Admin > Subdomain Logs** for error entries
3. The `ServerEventSubscriber` listens to PteroCA server events — verify the events are being dispatched

---

## Migration Errors

```bash
# Check migration status
php bin/console doctrine:migrations:status

# Run migrations manually
php bin/console doctrine:migrations:migrate

# Verify schema
php bin/console doctrine:schema:validate
```

---

## Checking Logs

### Application Logs
```bash
tail -f /var/www/pteroca/var/log/prod.log | grep -i subdomain
```

### Plugin Activity Logs
Go to **Admin > Subdomain Logs** to see all subdomain-related activity.

### DNS Sync
Use the **Sync DNS Records** button on the admin dashboard to verify all subdomains match their Cloudflare records.

## Getting Help

If you continue to experience issues:
1. Check plugin health: `php bin/console pteroca:plugin:health subdomains --detailed`
2. Check the activity logs for detailed error messages
3. Open an issue on [GitHub](https://github.com/chemanc/PteroCA-Subdomains/issues) with:
   - Error message (from logs)
   - PteroCA version
   - PHP version
   - Steps to reproduce
