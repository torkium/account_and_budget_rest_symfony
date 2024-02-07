<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240207225645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE budget (id INT AUTO_INCREMENT NOT NULL, financial_category_id INT DEFAULT NULL, bank_account_id INT NOT NULL, label VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, frequency VARCHAR(255) NOT NULL, INDEX IDX_73F2F77B5071677E (financial_category_id), INDEX IDX_73F2F77B12CB990C (bank_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77B5071677E FOREIGN KEY (financial_category_id) REFERENCES financial_category (id)');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77B12CB990C FOREIGN KEY (bank_account_id) REFERENCES bank_account (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77B5071677E');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77B12CB990C');
        $this->addSql('DROP TABLE budget');
    }
}
