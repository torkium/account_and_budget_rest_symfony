<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240208153038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ADD scheduled_transaction_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1AA222510 FOREIGN KEY (scheduled_transaction_id) REFERENCES scheduled_transaction (id)');
        $this->addSql('CREATE INDEX IDX_723705D1AA222510 ON transaction (scheduled_transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1AA222510');
        $this->addSql('DROP INDEX IDX_723705D1AA222510 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP scheduled_transaction_id');
    }
}
