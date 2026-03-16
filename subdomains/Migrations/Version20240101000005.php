<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initialize default settings for Subdomains plugin.
 *
 * Settings are stored with context = 'plugin:subdomains' to namespace them.
 * Uses storage types (string, integer, boolean) matching config_schema.
 * PteroCA maps these to display types automatically.
 */
final class Version20240101000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialize default settings for Subdomains plugin';
    }

    public function up(Schema $schema): void
    {
        // General settings (hierarchy: 100)
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('cloudflare_api_token', '', 'string', 'plugin:subdomains', 100, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('min_length', '3', 'integer', 'plugin:subdomains', 100, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('max_length', '32', 'integer', 'plugin:subdomains', 100, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('change_cooldown_hours', '24', 'integer', 'plugin:subdomains', 100, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('default_ttl', '1', 'string', 'plugin:subdomains', 100, false)");

        // Advanced settings (hierarchy: 200)
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('auto_delete_on_terminate', '1', 'boolean', 'plugin:subdomains', 200, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('auto_suspend_on_suspend', '1', 'boolean', 'plugin:subdomains', 200, false)");
        $this->addSql("INSERT IGNORE INTO setting (name, value, type, context, hierarchy, nullable) VALUES ('rate_limit', '5', 'integer', 'plugin:subdomains', 200, false)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM setting WHERE context = 'plugin:subdomains'");
    }
}
