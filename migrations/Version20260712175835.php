<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260712175835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consumer_favorite_business (consumer_id INT NOT NULL, business_id INT NOT NULL, INDEX IDX_5FF79CC437FDBD6D (consumer_id), INDEX IDX_5FF79CC4A89DB457 (business_id), PRIMARY KEY (consumer_id, business_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE consumer_favorite_business ADD CONSTRAINT FK_5FF79CC437FDBD6D FOREIGN KEY (consumer_id) REFERENCES consumer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consumer_favorite_business ADD CONSTRAINT FK_5FF79CC4A89DB457 FOREIGN KEY (business_id) REFERENCES business (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consumer_favorite_business DROP FOREIGN KEY FK_5FF79CC437FDBD6D');
        $this->addSql('ALTER TABLE consumer_favorite_business DROP FOREIGN KEY FK_5FF79CC4A89DB457');
        $this->addSql('DROP TABLE consumer_favorite_business');
    }
}
