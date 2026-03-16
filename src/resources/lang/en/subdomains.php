<?php

/**
 * PteroCA Subdomains - English Translations
 * 
 * @package PteroCA Subdomains
 * @author  XMA Corporation
 * @license MIT
 */

return [
    // ============================================
    // GENERAL
    // ============================================
    'title' => 'Subdomain Manager',
    'subdomain' => 'Subdomain',
    'subdomains' => 'Subdomains',
    'domain' => 'Domain',
    'status' => 'Status',
    'actions' => 'Actions',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'update' => 'Update',
    'search' => 'Search',
    'filter' => 'Filter',
    'export' => 'Export',
    'import' => 'Import',
    'refresh' => 'Refresh',
    'loading' => 'Loading...',
    'no_results' => 'No results found',
    'confirm' => 'Confirm',
    'yes' => 'Yes',
    'no' => 'No',

    // ============================================
    // USER INTERFACE (Client Area)
    // ============================================
    'your_subdomain' => 'Your Subdomain',
    'no_subdomain' => 'No subdomain configured for this server',
    'no_subdomain_hint' => 'Create a custom subdomain so players can connect easily to your server.',
    'create_subdomain' => 'Create Subdomain',
    'change_subdomain' => 'Change Subdomain',
    'delete_subdomain' => 'Delete Subdomain',
    'subdomain_placeholder' => 'Enter your desired subdomain',
    'subdomain_preview' => 'Your server address will be:',
    'connect_using' => 'Connect using',
    'copy_address' => 'Copy Address',
    'copied' => 'Copied!',
    'copy_failed' => 'Failed to copy',
    'server_address' => 'Server Address',
    'port' => 'Port',
    'full_address' => 'Full Address',

    // ============================================
    // STATUS
    // ============================================
    'status_pending' => 'Pending',
    'status_active' => 'Active',
    'status_suspended' => 'Suspended',
    'status_error' => 'Error',
    'dns_propagating' => 'DNS is propagating (may take up to 5 minutes)',
    'dns_active' => 'DNS records are active',
    'dns_suspended' => 'DNS records are suspended',
    'dns_error' => 'There was an error with the DNS records',

    // ============================================
    // VALIDATION MESSAGES
    // ============================================
    'subdomain_available' => 'Subdomain is available!',
    'subdomain_taken' => 'This subdomain is already taken',
    'subdomain_taken_cloudflare' => 'This subdomain already exists in DNS',
    'subdomain_blacklisted' => 'This subdomain is not allowed',
    'subdomain_invalid' => 'Invalid subdomain format. Use only letters, numbers, and hyphens.',
    'subdomain_invalid_start' => 'Subdomain cannot start with a hyphen',
    'subdomain_invalid_end' => 'Subdomain cannot end with a hyphen',
    'subdomain_invalid_consecutive' => 'Subdomain cannot have consecutive hyphens',
    'subdomain_too_short' => 'Subdomain must be at least :min characters',
    'subdomain_too_long' => 'Subdomain cannot exceed :max characters',
    'cooldown_active' => 'You can change your subdomain again in :time',
    'cooldown_hours' => ':hours hours',
    'cooldown_minutes' => ':minutes minutes',
    'checking_availability' => 'Checking availability...',

    // ============================================
    // SUCCESS MESSAGES
    // ============================================
    'subdomain_created' => 'Subdomain created successfully! DNS may take a few minutes to propagate.',
    'subdomain_updated' => 'Subdomain updated successfully! DNS may take a few minutes to propagate.',
    'subdomain_deleted' => 'Subdomain deleted successfully.',
    'settings_saved' => 'Settings saved successfully.',
    'blacklist_added' => 'Word added to blacklist.',
    'blacklist_removed' => 'Word removed from blacklist.',
    'blacklist_imported' => ':count words imported to blacklist.',
    'default_blacklist_loaded' => 'Default blacklist loaded successfully.',
    'dns_synced' => 'DNS records synchronized successfully.',
    'connection_test_success' => 'Connection to Cloudflare successful!',

    // ============================================
    // ERROR MESSAGES
    // ============================================
    'cloudflare_error' => 'DNS provider error. Please try again later.',
    'cloudflare_connection_failed' => 'Failed to connect to Cloudflare: :error',
    'cloudflare_record_failed' => 'Failed to create DNS record: :error',
    'server_not_found' => 'Server not found',
    'permission_denied' => 'You do not have permission to manage this subdomain',
    'already_has_subdomain' => 'This server already has a subdomain',
    'feature_disabled' => 'Subdomain feature is currently disabled',
    'invalid_domain' => 'Invalid domain selected',
    'domain_not_configured' => 'No domains have been configured. Please contact administrator.',
    'api_not_configured' => 'Cloudflare API is not configured. Please contact administrator.',
    'generic_error' => 'An error occurred. Please try again later.',
    'import_failed' => 'Failed to import blacklist: :error',
    'export_failed' => 'Failed to export data: :error',

    // ============================================
    // ADMIN INTERFACE
    // ============================================
    'admin_title' => 'Subdomain Management',
    'admin_description' => 'Manage custom subdomains for game servers',
    'dashboard' => 'Dashboard',
    'settings' => 'Settings',
    'blacklist' => 'Blacklist',
    'logs' => 'Activity Logs',
    'domains' => 'Domains',
    'statistics' => 'Statistics',

    // Statistics
    'total_subdomains' => 'Total Subdomains',
    'active_subdomains' => 'Active Subdomains',
    'pending_subdomains' => 'Pending Subdomains',
    'suspended_subdomains' => 'Suspended Subdomains',
    'error_subdomains' => 'Error Subdomains',
    'subdomains_today' => 'Created Today',
    'subdomains_this_week' => 'Created This Week',
    'subdomains_this_month' => 'Created This Month',

    // ============================================
    // ADMIN SETTINGS
    // ============================================
    'cloudflare_settings' => 'Cloudflare Settings',
    'api_token' => 'API Token',
    'api_token_placeholder' => 'Enter your Cloudflare API token',
    'api_token_help' => 'Create a token with "Edit zone DNS" permissions at Cloudflare Dashboard > My Profile > API Tokens',
    'zone_id' => 'Zone ID',
    'zone_id_placeholder' => 'Enter your Cloudflare Zone ID',
    'zone_id_help' => 'Found on your domain\'s Overview page in Cloudflare Dashboard (right sidebar)',
    'test_connection' => 'Test Connection',
    'testing_connection' => 'Testing connection...',
    'connection_status' => 'Connection Status',
    'connected' => 'Connected',
    'not_connected' => 'Not Connected',
    'last_tested' => 'Last tested: :time',

    'subdomain_settings' => 'Subdomain Settings',
    'min_length' => 'Minimum Length',
    'min_length_help' => 'Minimum number of characters for subdomains',
    'max_length' => 'Maximum Length',
    'max_length_help' => 'Maximum number of characters for subdomains',
    'change_cooldown' => 'Change Cooldown',
    'change_cooldown_help' => 'Hours users must wait before changing their subdomain again (0 = no cooldown)',
    'hours' => 'hours',
    'auto_delete' => 'Auto-delete on Server Termination',
    'auto_delete_help' => 'Automatically delete DNS records when a server is terminated',
    'auto_suspend' => 'Auto-suspend on Server Suspension',
    'auto_suspend_help' => 'Automatically disable DNS records when a server is suspended',
    'default_ttl' => 'Default TTL',
    'default_ttl_help' => 'Time-to-live for DNS records',
    'ttl_auto' => 'Auto',
    'ttl_1min' => '1 minute',
    'ttl_5min' => '5 minutes',
    'ttl_30min' => '30 minutes',
    'ttl_1hour' => '1 hour',
    'ttl_12hours' => '12 hours',
    'ttl_1day' => '1 day',

    'enabled' => 'Enabled',
    'disabled' => 'Disabled',

    // ============================================
    // DOMAIN MANAGEMENT
    // ============================================
    'domain_management' => 'Domain Management',
    'add_domain' => 'Add Domain',
    'edit_domain' => 'Edit Domain',
    'delete_domain' => 'Delete Domain',
    'domain_name' => 'Domain Name',
    'domain_name_placeholder' => 'example.com',
    'cloudflare_zone' => 'Cloudflare Zone ID',
    'is_default' => 'Default Domain',
    'is_active' => 'Active',
    'no_domains' => 'No domains configured',
    'domain_added' => 'Domain added successfully',
    'domain_updated' => 'Domain updated successfully',
    'domain_deleted' => 'Domain deleted successfully',
    'cannot_delete_domain_in_use' => 'Cannot delete domain that has active subdomains',
    'at_least_one_domain' => 'At least one active domain is required',

    // ============================================
    // BLACKLIST
    // ============================================
    'blacklist_title' => 'Blocked Subdomains',
    'blacklist_description' => 'Subdomains in this list cannot be used by users',
    'add_to_blacklist' => 'Add to Blacklist',
    'remove_from_blacklist' => 'Remove',
    'blacklist_word' => 'Word/Subdomain',
    'blacklist_word_placeholder' => 'Enter word to block',
    'blacklist_reason' => 'Reason (optional)',
    'blacklist_reason_placeholder' => 'Why is this blocked?',
    'import_blacklist' => 'Import',
    'export_blacklist' => 'Export',
    'import_blacklist_help' => 'Upload a text file with one word per line',
    'default_blacklist' => 'Load Default Blacklist',
    'default_blacklist_confirm' => 'This will add common reserved subdomains to the blacklist. Continue?',
    'clear_blacklist' => 'Clear All',
    'clear_blacklist_confirm' => 'Are you sure you want to remove all blacklisted words?',
    'blacklist_empty' => 'Blacklist is empty',
    'blacklist_count' => ':count blocked words',

    // ============================================
    // ACTIVITY LOGS
    // ============================================
    'logs_title' => 'Activity Logs',
    'logs_description' => 'Track all subdomain-related activities',
    'log_action' => 'Action',
    'log_user' => 'User',
    'log_subdomain' => 'Subdomain',
    'log_details' => 'Details',
    'log_ip' => 'IP Address',
    'log_date' => 'Date',
    'log_action_create' => 'Created',
    'log_action_update' => 'Updated',
    'log_action_delete' => 'Deleted',
    'log_action_suspend' => 'Suspended',
    'log_action_unsuspend' => 'Unsuspended',
    'log_action_error' => 'Error',
    'clear_logs' => 'Clear Logs',
    'clear_logs_confirm' => 'Are you sure you want to clear all logs?',
    'logs_cleared' => 'Logs cleared successfully',
    'no_logs' => 'No activity logs',
    'filter_by_action' => 'Filter by action',
    'filter_by_user' => 'Filter by user',
    'filter_by_date' => 'Filter by date',
    'all_actions' => 'All actions',

    // ============================================
    // BULK OPERATIONS
    // ============================================
    'bulk_operations' => 'Bulk Operations',
    'sync_dns' => 'Sync DNS Records',
    'sync_dns_description' => 'Synchronize all subdomains with Cloudflare DNS records',
    'sync_dns_confirm' => 'This will check and update all DNS records. Continue?',
    'export_subdomains' => 'Export Subdomains',
    'export_subdomains_description' => 'Download a CSV file with all subdomains',
    'purge_orphaned' => 'Purge Orphaned Records',
    'purge_orphaned_description' => 'Remove DNS records that no longer have an associated server',
    'purge_orphaned_confirm' => 'This will delete DNS records without associated servers. Continue?',

    // ============================================
    // CONFIRMATIONS
    // ============================================
    'confirm_delete' => 'Are you sure you want to delete this subdomain?',
    'confirm_delete_text' => 'This will remove the DNS records. Users will no longer be able to connect using this address. This action cannot be undone.',
    'confirm_change' => 'Are you sure you want to change your subdomain?',
    'confirm_change_text' => 'Your old subdomain will stop working immediately. Players using the old address will need to use the new one.',
    'confirm_action' => 'Confirm Action',

    // ============================================
    // TABLE HEADERS
    // ============================================
    'table_subdomain' => 'Subdomain',
    'table_server' => 'Server',
    'table_user' => 'User',
    'table_domain' => 'Domain',
    'table_status' => 'Status',
    'table_created' => 'Created',
    'table_updated' => 'Updated',
    'table_actions' => 'Actions',

    // ============================================
    // TOOLTIPS & HELP TEXT
    // ============================================
    'help_subdomain_format' => 'Use only lowercase letters, numbers, and hyphens. Cannot start or end with a hyphen.',
    'help_dns_propagation' => 'DNS changes may take up to 5 minutes to propagate worldwide.',
    'help_srv_record' => 'SRV records allow players to connect without specifying the port.',
    'help_api_token_security' => 'Your API token is stored encrypted and never displayed after saving.',

    // ============================================
    // MINECRAFT SPECIFIC
    // ============================================
    'minecraft_connect' => 'Minecraft Server Address',
    'minecraft_java' => 'Java Edition',
    'minecraft_bedrock' => 'Bedrock Edition',
    'minecraft_port_note' => 'Thanks to SRV records, players don\'t need to enter the port!',

    // ============================================
    // TIME FORMATS
    // ============================================
    'time_just_now' => 'Just now',
    'time_minutes_ago' => ':minutes minutes ago',
    'time_hours_ago' => ':hours hours ago',
    'time_days_ago' => ':days days ago',
    'time_in_minutes' => 'in :minutes minutes',
    'time_in_hours' => 'in :hours hours',

    // ============================================
    // NAVIGATION & BREADCRUMBS
    // ============================================
    'nav_subdomains' => 'Subdomains',
    'nav_settings' => 'Subdomain Settings',
    'nav_blacklist' => 'Subdomain Blacklist',
    'nav_logs' => 'Subdomain Logs',
    'breadcrumb_home' => 'Home',
    'breadcrumb_admin' => 'Admin',
    'breadcrumb_servers' => 'Servers',
];
