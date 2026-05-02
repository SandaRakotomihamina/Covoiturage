<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418143519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ride_reminder (id INT AUTO_INCREMENT NOT NULL, sent_at DATETIME NOT NULL, ride_id INT NOT NULL, passenger_id INT NOT NULL, INDEX IDX_3F1AEDD4302A8A70 (ride_id), INDEX IDX_3F1AEDD44502E565 (passenger_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ride_reminder ADD CONSTRAINT FK_3F1AEDD4302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id)');
        $this->addSql('ALTER TABLE ride_reminder ADD CONSTRAINT FK_3F1AEDD44502E565 FOREIGN KEY (passenger_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE car_model car_model VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ride_reminder DROP FOREIGN KEY FK_3F1AEDD4302A8A70');
        $this->addSql('ALTER TABLE ride_reminder DROP FOREIGN KEY FK_3F1AEDD44502E565');
        $this->addSql('DROP TABLE ride_reminder');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE `user` DROP phone, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE car_model car_model VARCHAR(255) DEFAULT \'NULL\'');
    }
}
