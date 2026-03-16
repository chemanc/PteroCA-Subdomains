<?php

/**
 * PteroCA Subdomains - Configuration
 *
 * Default configuration values for the Subdomains plugin.
 * Settings stored in the database (pteroca_subdomain_settings) take
 * precedence over these values at runtime.
 *
 * @package PteroCA Subdomains
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Plugin Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch to enable or disable the subdomain feature globally.
    |
    */
    'enabled' => env('SUBDOMAIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Subdomain Length Constraints
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum number of characters allowed for subdomains.
    |
    */
    'min_length' => 3,
    'max_length' => 32,

    /*
    |--------------------------------------------------------------------------
    | Change Cooldown
    |--------------------------------------------------------------------------
    |
    | Number of hours a user must wait before changing their subdomain again.
    | Set to 0 to disable the cooldown.
    |
    */
    'change_cooldown_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Server Lifecycle Automation
    |--------------------------------------------------------------------------
    |
    | Control automatic DNS management when servers change state.
    |
    */
    'auto_delete_on_terminate' => true,
    'auto_suspend_on_suspend' => true,

    /*
    |--------------------------------------------------------------------------
    | Default TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live for DNS records in seconds.
    | 1 = Auto (Cloudflare managed), 60, 300, 1800, 3600, 43200, 86400
    |
    */
    'default_ttl' => 1,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of subdomain API requests per minute per user.
    |
    */
    'rate_limit' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default Blacklist
    |--------------------------------------------------------------------------
    |
    | Common reserved words that should be blocked as subdomains.
    | These are loaded into the database via the admin panel.
    |
    */
    'default_blacklist' => [
        // System / Infrastructure
        'admin', 'administrator', 'api', 'app', 'billing', 'blog', 'cdn', 'cpanel',
        'dashboard', 'dev', 'docs', 'email', 'ftp', 'git', 'help', 'mail',
        'manage', 'mysql', 'ns1', 'ns2', 'panel', 'pop', 'root', 'server',
        'shop', 'smtp', 'ssl', 'staging', 'static', 'store', 'support', 'test',
        'vpn', 'webmail', 'whm', 'www',

        // Abuse / Postmaster
        'abuse', 'postmaster', 'hostmaster', 'security', 'noc',

        // User / Account
        'admin1', 'user', 'users', 'login', 'register',
        'account', 'accounts', 'settings', 'config', 'system', 'sys',

        // Network / Internal
        'localhost', 'local', 'internal', 'private', 'public',

        // Assets / Files
        'assets', 'images', 'img', 'css', 'js', 'fonts', 'media',
        'download', 'downloads', 'upload', 'uploads',
        'files', 'file', 'data', 'database', 'db',

        // Backup / Temp
        'backup', 'backups', 'temp', 'tmp', 'cache', 'log', 'logs',

        // Gaming / Pterodactyl
        'minecraft', 'mc', 'play', 'game', 'games', 'server1', 'server2',
        'node', 'node1', 'node2', 'wings', 'pterodactyl', 'pteroca',
    ],

];
