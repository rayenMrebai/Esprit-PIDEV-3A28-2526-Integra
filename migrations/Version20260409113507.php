<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409113507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (userid INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, passwordHash VARCHAR(255) NOT NULL, role ENUM(\'ADMINISTRATEUR\', \'MANAGER\', \'EMPLOYE\'), isActive TINYINT NOT NULL, lastLogin DATETIME DEFAULT NULL, accountCreatedDate DATETIME NOT NULL, accountStatus ENUM(\'ACTIVE\', \'SUSPENDED\', \'DISABLED\'), PRIMARY KEY (userid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_skill (user_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_BCFF1F2FA76ED395 (user_id), INDEX IDX_BCFF1F2F5585C142 (skill_id), PRIMARY KEY (user_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_skill ADD CONSTRAINT FK_BCFF1F2FA76ED395 FOREIGN KEY (user_id) REFERENCES user (userid)');
        $this->addSql('ALTER TABLE user_skill ADD CONSTRAINT FK_BCFF1F2F5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id)');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY `training_program_skill_ibfk_1`');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY `training_program_skill_ibfk_2`');
        $this->addSql('DROP TABLE training_program_skill');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY `FK_FE2E314ABEFD98D1`');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314ABEFD98D1 FOREIGN KEY (training_id) REFERENCES training_program (id)');
        $this->addSql('DROP INDEX training_id ON quiz_result');
        $this->addSql('CREATE INDEX IDX_FE2E314ABEFD98D1 ON quiz_result (training_id)');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY `skill_ibfk_1`');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY `skill_ibfk_1`');
        $this->addSql('ALTER TABLE skill CHANGE description description LONGTEXT DEFAULT NULL, CHANGE level_required level_required INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE4771DA8C5E3 FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id)');
        $this->addSql('DROP INDEX trainingprogram_id ON skill');
        $this->addSql('CREATE INDEX IDX_5E3DE4771DA8C5E3 ON skill (trainingprogram_id)');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE training_program CHANGE description description LONGTEXT DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE training_program_skill (training_program_id INT NOT NULL, skill_id INT NOT NULL, INDEX skill_id (skill_id), INDEX IDX_BEFDF8108406BD6C (training_program_id), PRIMARY KEY (training_program_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT `training_program_skill_ibfk_1` FOREIGN KEY (training_program_id) REFERENCES training_program (id)');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT `training_program_skill_ibfk_2` FOREIGN KEY (skill_id) REFERENCES skill (id)');
        $this->addSql('ALTER TABLE user_skill DROP FOREIGN KEY FK_BCFF1F2FA76ED395');
        $this->addSql('ALTER TABLE user_skill DROP FOREIGN KEY FK_BCFF1F2F5585C142');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_skill');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314ABEFD98D1');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT `FK_FE2E314ABEFD98D1` FOREIGN KEY (training_id) REFERENCES training_program (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_fe2e314abefd98d1 ON quiz_result');
        $this->addSql('CREATE INDEX training_id ON quiz_result (training_id)');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE4771DA8C5E3');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE4771DA8C5E3');
        $this->addSql('ALTER TABLE skill CHANGE description description TEXT DEFAULT NULL, CHANGE level_required level_required INT DEFAULT 1');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_5e3de4771da8c5e3 ON skill');
        $this->addSql('CREATE INDEX trainingprogram_id ON skill (trainingprogram_id)');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE4771DA8C5E3 FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id)');
        $this->addSql('ALTER TABLE training_program CHANGE description description TEXT DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'PROGRAMMÉ\'');
    }
}
