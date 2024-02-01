<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240128165245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_bank_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, bank_account_id INT NOT NULL, profile_id INT DEFAULT NULL, permissions INT NOT NULL, INDEX IDX_D36E4208A76ED395 (user_id), INDEX IDX_D36E420812CB990C (bank_account_id), INDEX IDX_D36E4208CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_bank_account ADD CONSTRAINT FK_D36E4208A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_bank_account ADD CONSTRAINT FK_D36E420812CB990C FOREIGN KEY (bank_account_id) REFERENCES bank_account (id)');
        $this->addSql('ALTER TABLE user_bank_account ADD CONSTRAINT FK_D36E4208CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_bank_account DROP FOREIGN KEY FK_D36E4208A76ED395');
        $this->addSql('ALTER TABLE user_bank_account DROP FOREIGN KEY FK_D36E420812CB990C');
        $this->addSql('ALTER TABLE user_bank_account DROP FOREIGN KEY FK_D36E4208CCFA12B8');
        $this->addSql('DROP TABLE user_bank_account');
    }
}
