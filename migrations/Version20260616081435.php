<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616081435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne image_car à la table `user` pour stocker le chemin de la photo de la voiture';
    }

    public function up(Schema $schema): void
    {
        // ajouter la colonne image_car (nullable)
        $this->addSql("ALTER TABLE `user` ADD image_car VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        // retirer la colonne image_car
        $this->addSql("ALTER TABLE `user` DROP image_car");
    }
}
