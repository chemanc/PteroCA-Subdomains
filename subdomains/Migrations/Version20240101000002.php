<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create plg_sub_subdomains table for user subdomains.
 */
final class Version20240101000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plg_sub_subdomains table for Subdomains plugin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE plg_sub_subdomains (
                id INT AUTO_INCREMENT NOT NULL,
                server_id INT NOT NULL,
                user_id INT NOT NULL,
                subdomain VARCHAR(63) NOT NULL,
                domain_id INT NOT NULL,
                cloudflare_a_record_id VARCHAR(64) DEFAULT NULL,
                cloudflare_srv_record_id VARCHAR(64) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                error_message LONGTEXT DEFAULT NULL,
                last_changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX uniq_subdomain_domain (subdomain, domain_id),
                INDEX idx_server_id (server_id),
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_domain_id (domain_id),
                CONSTRAINT fk_subdomain_domain FOREIGN KEY (domain_id) REFERENCES plg_sub_domains (id) ON DELETE RESTRICT,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS plg_sub_subdomains');
    }
}
