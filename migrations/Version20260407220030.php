<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407220030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE salaire DROP FOREIGN KEY `fk_salaire_user`');
        $this->addSql('DROP TABLE bonus_rule');
        $this->addSql('DROP TABLE salaire');
        $this->addSql('DROP TABLE user_account');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bonus_rule (id INT AUTO_INCREMENT NOT NULL, salaryId INT NOT NULL, nomRegle VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, percentage DOUBLE PRECISION DEFAULT \'0\' NOT NULL, bonus DOUBLE PRECISION DEFAULT \'0\' NOT NULL, condition_text TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status ENUM(\'CRÉE\', \'ACTIVE\') CHARACTER SET utf8mb4 DEFAULT \'CRÉE\' NOT NULL COLLATE `utf8mb4_general_ci`, createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_bonusrule_salaire (salaryId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE salaire (id INT AUTO_INCREMENT NOT NULL, userId INT NOT NULL, baseAmount DOUBLE PRECISION NOT NULL, bonusAmount DOUBLE PRECISION DEFAULT \'0\' NOT NULL, totalAmount DOUBLE PRECISION NOT NULL, status ENUM(\'CREÉ\', \'EN_COURS\', \'PAYÉ\') CHARACTER SET utf8mb4 DEFAULT \'CREÉ\' NOT NULL COLLATE `utf8mb4_general_ci`, datePaiement DATE NOT NULL, createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_salaire_user (userId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_account (userId INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, passwordHash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, role ENUM(\'ADMINISTRATEUR\', \'MANAGER\', \'EMPLOYE\') CHARACTER SET utf8mb4 DEFAULT \'EMPLOYE\' NOT NULL COLLATE `utf8mb4_general_ci`, isActive TINYINT DEFAULT 1 NOT NULL, lastLogin DATETIME DEFAULT NULL, accountCreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, accountStatus ENUM(\'ACTIVE\', \'SUSPENDED\', \'DISABLED\') CHARACTER SET utf8mb4 DEFAULT \'ACTIVE\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY (userId)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE salaire ADD CONSTRAINT `fk_salaire_user` FOREIGN KEY (userId) REFERENCES user_account (userId) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
