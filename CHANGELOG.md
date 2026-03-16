# Changelog

All notable changes to PteroCA Subdomains will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Subdomain CRUD operations (create, update, delete per server)
- Cloudflare DNS integration (A + SRV records for Minecraft)
- Admin dashboard with statistics
- Admin settings panel (Cloudflare API, subdomain rules, TTL, cooldown)
- Multi-domain support with per-domain Cloudflare zones
- Blacklist management (add, remove, import, export, default list)
- Activity logging with filters
- Server lifecycle automation (auto-suspend, auto-delete on termination)
- Real-time subdomain availability check (AJAX)
- Copy-to-clipboard for server addresses
- Rate limiting middleware
- Form validation with custom rules
- Cooldown system for subdomain changes
- Bulk operations (DNS sync, CSV export)
- English and Spanish translations
- Complete documentation (installation, configuration, Cloudflare setup, troubleshooting)
