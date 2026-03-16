<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create plg_sub_blacklist table for blocked subdomain words.
 */
final class Version20240101000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plg_sub_blacklist table for Subdomains plugin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE plg_sub_blacklist (
                id INT AUTO_INCREMENT NOT NULL,
                word VARCHAR(63) NOT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX uniq_word (word),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS plg_sub_blacklist');
    }
}
