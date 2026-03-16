# Troubleshooting

## Common Issues

### "Cloudflare API is not configured"

**Cause:** No API token has been saved in settings.

**Fix:** Go to Admin > Subdomains > Settings and enter your Cloudflare API token.

---

### "Failed to connect to Cloudflare"

**Cause:** Invalid API token or Zone ID.

**Fix:**
1. Verify your API token has "Zone:DNS:Edit" permissions
2. Verify the Zone ID matches your domain
3. Check if the token has expired
4. Try creating a new API token

---

### DNS Not Resolving After Creating Subdomain

**Cause:** DNS propagation delay.

**Fix:**
- Wait up to 5 minutes for DNS propagation
- Check the subdomain status in the admin panel
- Use `nslookup` or `dig` to verify:

```bash
# Check A record
nslookup myserver.yourdomain.com

# Check SRV record
nslookup -type=SRV _minecraft._tcp.myserver.yourdomain.com
```

---

### "This subdomain already exists in DNS"

**Cause:** A DNS record with that name already exists in Cloudflare (possibly created manually).

**Fix:**
1. Check your Cloudflare DNS dashboard for existing records
2. Delete the conflicting record manually
3. Try creating the subdomain again

---

### "This subdomain is not allowed"

**Cause:** The subdomain matches a blacklisted word.

**Fix:**
- If the word shouldn't be blocked, remove it from the blacklist in Admin > Subdomains > Blacklist
- The blacklist checks both exact matches and substrings

---

### Server Address Shows Wrong IP/Port

**Cause:** The plugin reads the server's allocation IP and port from PteroCA.

**Fix:**
1. Verify the server's allocation is correct in PteroCA
2. If changed, delete and recreate the subdomain
3. Check that the server node IP is accessible externally

---

### Rate Limit Errors (429)

**Cause:** Too many requests in a short period.

**Fix:**
- Wait 1 minute and try again
- Admin can adjust the rate limit in settings (default: 5 req/min)

---

### Migration Errors

**Cause:** Database compatibility or permission issues.

**Fix:**
```bash
# Check migration status
php artisan migrate:status

# Run with verbose output
php artisan migrate -v

# If tables already exist partially, rollback and retry
php artisan migrate:rollback --step=1
php artisan migrate
```

---

### Subdomain Not Deleted After Server Termination

**Cause:** Auto-delete may be disabled, or the observer failed.

**Fix:**
1. Check Admin > Settings > "Auto-delete on Server Termination" is enabled
2. Check Admin > Logs for error entries
3. Manually delete the subdomain from the admin panel
4. Check Cloudflare DNS dashboard for orphaned records

---

## Checking Logs

### Application Logs

```bash
tail -f /var/www/pteroca/storage/logs/laravel.log | grep -i subdomain
```

### Activity Logs

Go to **Admin > Subdomains > Logs** to see all subdomain-related activity.

### DNS Sync

Use **Admin > Subdomains > Sync DNS Records** to verify all subdomains match their Cloudflare records. This will flag any discrepancies.

## Getting Help

If you continue to experience issues:

1. Check the [activity logs](#checking-logs) for detailed error messages
2. Review your Cloudflare API token permissions
3. Open an issue on the GitHub repository with:
   - Error message (from logs)
   - PteroCA version
   - PHP version
   - Steps to reproduce
