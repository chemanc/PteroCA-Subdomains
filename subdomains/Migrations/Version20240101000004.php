<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create plg_sub_logs table for activity audit trail.
 */
final class Version20240101000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plg_sub_logs table for Subdomains plugin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE plg_sub_logs (
                id INT AUTO_INCREMENT NOT NULL,
                subdomain_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,
                action VARCHAR(20) NOT NULL,
                details JSON DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX idx_action (action),
                INDEX idx_log_created_at (created_at),
                INDEX idx_log_subdomain_id (subdomain_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS plg_sub_logs');
    }
}
