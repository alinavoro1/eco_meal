<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713163629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consumer_preferred_category (consumer_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_6EC62AF937FDBD6D (consumer_id), INDEX IDX_6EC62AF912469DE2 (category_id), PRIMARY KEY (consumer_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sale_record (id INT AUTO_INCREMENT NOT NULL, package_name VARCHAR(100) NOT NULL, package_price DOUBLE PRECISION NOT NULL, category_name VARCHAR(50) DEFAULT NULL, ordered_at DATETIME NOT NULL, fulfilled_at DATETIME NOT NULL, business_id INT NOT NULL, INDEX IDX_EF559153A89DB457 (business_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE consumer_preferred_category ADD CONSTRAINT FK_6EC62AF937FDBD6D FOREIGN KEY (consumer_id) REFERENCES consumer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consumer_preferred_category ADD CONSTRAINT FK_6EC62AF912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_record ADD CONSTRAINT FK_EF559153A89DB457 FOREIGN KEY (business_id) REFERENCES business (id)');
        $this->addSql('ALTER TABLE `order` ADD fulfilled_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consumer_preferred_category DROP FOREIGN KEY FK_6EC62AF937FDBD6D');
        $this->addSql('ALTER TABLE consumer_preferred_category DROP FOREIGN KEY FK_6EC62AF912469DE2');
        $this->addSql('ALTER TABLE sale_record DROP FOREIGN KEY FK_EF559153A89DB457');
        $this->addSql('DROP TABLE consumer_preferred_category');
        $this->addSql('DROP TABLE sale_record');
        $this->addSql('ALTER TABLE `order` DROP fulfilled_at');
    }
}
