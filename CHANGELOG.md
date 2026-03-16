# Changelog

All notable changes to PteroCA Subdomains will be documented in this file.

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
