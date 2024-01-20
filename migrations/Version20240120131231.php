<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240120131231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO `user` (`id`, `email`, `username`, `roles`, `password`) VALUES (NULL, \'toto@gmail.com\', \'toto\', \'[]\', \'$2y$13$HheqzOj3.QLpq0U00MMpS.KiJsHcP3DDPlk5ZmKF6h0dPxbdkepSq\')');
        $this->addSql('INSERT INTO `user` (`id`, `email`, `username`, `roles`, `password`) VALUES (NULL, \'titi@gmail.com\', \'titi\', \'[]\', \'$2y$13$HheqzOj3.QLpq0U00MMpS.KiJsHcP3DDPlk5ZmKF6h0dPxbdkepSq\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
    }
}
