<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408115809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY `quiz_result_ibfk_1`');
        $this->addSql('DROP INDEX unique_user_training ON quiz_result');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT NOT NULL, CHANGE training_id training_id INT DEFAULT NULL, CHANGE completed_at completed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314ABEFD98D1 FOREIGN KEY (training_id) REFERENCES training_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX training_id TO IDX_FE2E314ABEFD98D1');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY `skill_ibfk_1`');
        $this->addSql('ALTER TABLE skill CHANGE id id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE level_required level_required INT NOT NULL, CHANGE categorie categorie VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE4771DA8C5E3 FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill RENAME INDEX trainingprogram_id TO IDX_5E3DE4771DA8C5E3');
        $this->addSql('ALTER TABLE training_program CHANGE id id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE duration duration INT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE start_date start_date DATE NOT NULL, CHANGE end_date end_date DATE NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY `training_program_skill_ibfk_1`');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY `training_program_skill_ibfk_2`');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT FK_BEFDF8108406BD6C FOREIGN KEY (training_program_id) REFERENCES training_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT FK_BEFDF8105585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_program_skill RENAME INDEX skill_id TO IDX_BEFDF8105585C142');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314ABEFD98D1');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE training_id training_id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT `quiz_result_ibfk_1` FOREIGN KEY (training_id) REFERENCES training_program (id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_training ON quiz_result (user_id, training_id)');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX idx_fe2e314abefd98d1 TO training_id');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE4771DA8C5E3');
        $this->addSql('ALTER TABLE skill CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE level_required level_required INT DEFAULT 1, CHANGE categorie categorie VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (trainingprogram_id) REFERENCES training_program (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE skill RENAME INDEX idx_5e3de4771da8c5e3 TO trainingprogram_id');
        $this->addSql('ALTER TABLE training_program CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE duration duration INT DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT \'NULL\', CHANGE start_date start_date DATE DEFAULT \'NULL\', CHANGE end_date end_date DATE DEFAULT \'NULL\', CHANGE status status VARCHAR(20) DEFAULT \'\'\'PROGRAMMÉ\'\'\'');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY FK_BEFDF8108406BD6C');
        $this->addSql('ALTER TABLE training_program_skill DROP FOREIGN KEY FK_BEFDF8105585C142');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT `training_program_skill_ibfk_1` FOREIGN KEY (training_program_id) REFERENCES training_program (id)');
        $this->addSql('ALTER TABLE training_program_skill ADD CONSTRAINT `training_program_skill_ibfk_2` FOREIGN KEY (skill_id) REFERENCES skill (id)');
        $this->addSql('ALTER TABLE training_program_skill RENAME INDEX idx_befdf8105585c142 TO skill_id');
    }
}
