<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240201115702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE financial_category (id INT AUTO_INCREMENT NOT NULL, financial_category_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_AB7952C55071677E (financial_category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE financial_category ADD CONSTRAINT FK_AB7952C55071677E FOREIGN KEY (financial_category_id) REFERENCES financial_category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE financial_category DROP FOREIGN KEY FK_AB7952C55071677E');
        $this->addSql('DROP TABLE financial_category');
    }
}
