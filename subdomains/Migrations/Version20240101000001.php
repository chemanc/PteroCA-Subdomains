<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create plg_sub_domains table for configurable domains.
 */
final class Version20240101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plg_sub_domains table for Subdomains plugin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE plg_sub_domains (
                id INT AUTO_INCREMENT NOT NULL,
                domain VARCHAR(255) NOT NULL,
                cloudflare_zone_id VARCHAR(64) NOT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX uniq_domain (domain),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS plg_sub_domains');
    }
}
