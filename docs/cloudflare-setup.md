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
5. Use the **Edit zone DNS** template, or create a custom token:

### Required Permissions

| Permission | Access |
|-----------|--------|
| Zone > DNS | **Edit** |

### Zone Resources

| Resource | Value |
|----------|-------|
| Include | Specific zone > **your-domain.com** |

If you have multiple domains, include all of them.

6. Click **Continue to summary** > **Create Token**
7. **Copy the token immediately** — it won't be shown again!

## Step 3: Configure in PteroCA

1. Go to **Admin > Settings > Plugins**
2. Find the **Subdomain Manager** section
3. Paste your API token in the **Cloudflare API Token** field and save
4. Go to **Admin > Subdomains > Domains**
5. Add your domain with its Zone ID
6. Click **Test Connection** to verify

## Important Notes

### DNS-Only Mode (No Proxy)

All DNS records created by this plugin use **DNS-only mode** (grey cloud). This is required because:
- Game servers use non-HTTP protocols
- SRV records cannot be proxied
- Direct IP connection is needed for game clients

### API Rate Limits

Cloudflare API allows 1,200 requests per 5 minutes. The plugin handles this with:
- Retry logic on server errors
- Rate limiting on user-facing endpoints
- Atomic operations (A + SRV created together with rollback)

### Token Security

The API token is stored encrypted in PteroCA's settings database (password-type fields are automatically encrypted by the framework). It is never displayed after saving.
