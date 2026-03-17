# Changelog

All notable changes to PteroCA Subdomains will be documented in this file.

## [1.0.2] - 2026-03-16

### Security
- CSRF protection on all state-changing forms (client + admin)
- `#[IsGranted('ROLE_ADMIN')]` on all admin API endpoints
- `#[IsGranted('IS_AUTHENTICATED_FULLY')]` on all client endpoints
- XSS prevention: HTML escaping on all dynamic JavaScript output
- Generic error messages (no internal Cloudflare/server details exposed)
- Input validation: domain format regex, Zone ID hex format validation
- Removed 0.0.0.0 IP fallback (throws exception instead of creating bogus DNS)

### Changed
- Admin menu moved to ADMINISTRACIÓN section as collapsible "Subdomains" submenu
- Admin menu items only visible to admins (not regular users)

### Added
- DRY footer component (`components/footer.html.twig`) with copyright + Buy Me a Coffee
- Footer included in dashboard, domains, and client tab pages

## [1.0.1] - 2026-03-16

### Fixed
- Cloudflare SRV record format updated (deprecated fields removed post May 2024)
- Server IP/port now fetched from Pterodactyl API instead of hardcoded fallback
- Entity column names fixed (`cloudflare_a_record_id` mapping)
- Config schema types corrected (`string`/`integer`/`boolean` instead of `password`/`select`)
- Settings migration removed (PteroCA auto-generates from `config_schema`)

### Changed
- Admin pages now use EasyAdmin CrudControllers (proper sidebar integration)
- Templates extend `@EasyAdmin/layout.html.twig` for consistent panel UI
- Menu items use `linkToCrud()` for EasyAdmin routing
- API endpoints moved to `/admin/subdomains-api/` prefix
- Form submissions redirect back to server tab (`#subdomain`)

### Added
- **Server tab "Subdomain"** integrated in server management page
- Subdomain replaces IP in console "DIRECCIÓN" card when active
- AJAX-loaded tab content with availability checker
- Copy-to-clipboard for subdomain address
- GitHub Actions auto-build ZIP on push to main

## [2.0.0] - 2026-03-16

### Changed
- **Complete rewrite from Laravel to PteroCA native plugin (Symfony 7)**
- Eloquent models → Doctrine ORM entities with PHP 8 attributes
- Blade templates → Twig templates
- Laravel routes → Symfony `#[Route]` attributes on controllers
- Laravel ServiceProvider → PteroCA `Bootstrap.php` + `plugin.json`
- Laravel migrations → Doctrine migrations with raw SQL
- PHP translation arrays → YAML translation files
- Laravel Http facade → Symfony HttpClient
- Eloquent Observer → Symfony EventSubscriber
- Manual admin views → EasyAdmin CRUD controllers (blacklist + logs)
- Table prefix changed from `pteroca_` to `plg_sub_`
- Settings now use PteroCA's native `setting` table with auto-generated UI

### Added
- `plugin.json` manifest with `config_schema` for auto-generated settings UI
- EasyAdmin CRUD for blacklist management (add/remove/search)
- EasyAdmin CRUD for activity logs (view/filter/search)
- `MenuEventSubscriber` for admin navigation integration
- `ServerEventSubscriber` for server lifecycle automation
- Plugin installable via ZIP upload in Admin Panel

### Removed
- Laravel ServiceProvider, middleware, form requests, custom rules
- Blade templates and `@push('scripts')` pattern
- `pteroca_subdomain_settings` custom table (replaced by PteroCA's `setting` table)

## [1.0.0] - 2026-03-16

### Added
- Initial release (Laravel version)
- Subdomain CRUD operations (create, update, delete per server)
- Cloudflare DNS integration (A + SRV records for Minecraft)
- Admin dashboard with statistics
- Admin settings panel
- Multi-domain support
- Blacklist management
- Activity logging
- Server lifecycle automation
- Real-time subdomain availability check
- English and Spanish translations
