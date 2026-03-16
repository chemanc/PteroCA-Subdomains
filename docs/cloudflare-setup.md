# Cloudflare Setup Guide

## Step 1: Get Your Zone ID

1. Log in to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Select your domain
3. On the **Overview** page, scroll down on the right sidebar
4. Find **Zone ID** and copy it

## Step 2: Create an API Token

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Click your profile icon (top right) > **My Profile**
3. Go to **API Tokens** tab
4. Click **Create Token**
5. Use the **Edit zone DNS** template, or create custom:

### Required Permissions

| Permission | Access |
|-----------|--------|
| Zone > DNS | **Edit** |

### Zone Resources

| Resource | Value |
|----------|-------|
| Include | Specific zone > **your-domain.com** |

If you have multiple domains, include all of them.

6. Click **Continue to summary**
7. Click **Create Token**
8. **Copy the token immediately** — it won't be shown again!

## Step 3: Configure in PteroCA

1. Go to **Admin > Subdomains > Settings**
2. Paste the API token in the **API Token** field
3. Add your domain:
   - **Domain Name**: `yourdomain.com`
   - **Zone ID**: (paste from Step 1)
   - Check **Default** if this is your primary domain
4. Click **Add Domain**
5. Click **Test Connection** to verify everything works

## Important Notes

### DNS-Only Mode (No Proxy)

All DNS records created by this plugin use **DNS-only mode** (grey cloud in Cloudflare). This is required because:

- Game servers use non-HTTP protocols
- SRV records cannot be proxied
- Direct IP connection is needed for game clients

### API Rate Limits

Cloudflare API has a rate limit of **1,200 requests per 5 minutes**. The plugin includes:

- Retry logic (3 attempts with 1-second delay)
- Rate limiting on user-facing endpoints
- Caching where appropriate

Under normal usage, you should never hit these limits.

### Token Security

- The API token is **encrypted** before being stored in the database
- It is **never displayed** after saving (shown as `********`)
- Tokens are transmitted securely over HTTPS

### Multiple Domains

You can configure multiple domains, each with its own Cloudflare Zone ID. Users will be able to choose which domain to use when creating a subdomain.

## Troubleshooting

If the test connection fails:

1. **Verify the Zone ID** — Make sure it matches the correct domain
2. **Check token permissions** — Must have "Zone:DNS:Edit"
3. **Check token zone resources** — Must include the correct zone
4. **Try creating a new token** — Tokens can expire or be revoked

See [troubleshooting.md](troubleshooting.md) for more help.
