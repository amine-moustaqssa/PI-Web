<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224094452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ResetPasswordRequest (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_35370143A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ResetPasswordRequest ADD CONSTRAINT FK_35370143A76ED395 FOREIGN KEY (user_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE ConstanteVitale ADD CONSTRAINT FK_731B9D8162FF6CDF FOREIGN KEY (consultation_id) REFERENCES Consultation (id)');
        $this->addSql('CREATE INDEX IDX_731B9D8162FF6CDF ON ConstanteVitale (consultation_id)');
        $this->addSql('ALTER TABLE Consultation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE rdv_id rdv_id INT DEFAULT NULL, CHANGE medecin_id medecin_id INT NOT NULL, CHANGE date_effectuee date_effectuee DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE notes_privees notes_privees LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE Consultation RENAME INDEX medecin_id TO IDX_8E775E5E4F31A84');
        $this->addSql('DROP INDEX code ON Departement');
        $this->addSql('ALTER TABLE Departement CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE code code VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE Disponibilite DROP FOREIGN KEY `Disponibilite_ibfk_1`');
        $this->addSql('ALTER TABLE Disponibilite CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE medecin_id medecin_id INT NOT NULL, CHANGE jour_semaine jour_semaine INT NOT NULL, CHANGE est_recurrent est_recurrent TINYINT NOT NULL');
        $this->addSql('ALTER TABLE Disponibilite ADD CONSTRAINT FK_9FC485DA4F31A84 FOREIGN KEY (medecin_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE Disponibilite RENAME INDEX medecin_id TO IDX_9FC485DA4F31A84');
        $this->addSql('ALTER TABLE DossierClinique DROP FOREIGN KEY `DossierClinique_ibfk_1`');
        $this->addSql('ALTER TABLE DossierClinique CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE profil_id profil_id INT NOT NULL, CHANGE antecedents antecedents LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE DossierClinique ADD CONSTRAINT FK_45853F19275ED078 FOREIGN KEY (profil_id) REFERENCES ProfilMedical (id)');
        $this->addSql('ALTER TABLE DossierClinique RENAME INDEX profil_id TO UNIQ_45853F19275ED078');
        $this->addSql('ALTER TABLE Facture DROP INDEX fk_facture_consultation, ADD UNIQUE INDEX UNIQ_313B5D8C62FF6CDF (consultation_id)');
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY `fk_facture_consultation`');
        $this->addSql('ALTER TABLE Facture CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE consultation_id consultation_id INT NOT NULL, CHANGE titulaire_id titulaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8C62FF6CDF FOREIGN KEY (consultation_id) REFERENCES Consultation (id)');
        $this->addSql('ALTER TABLE Facture RENAME INDEX fk_facture_titulaire TO IDX_313B5D8CA10273AA');
        $this->addSql('ALTER TABLE Paiement DROP FOREIGN KEY `fk_paiement_facture`');
        $this->addSql('ALTER TABLE Paiement CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE facture_id facture_id INT NOT NULL');
        $this->addSql('ALTER TABLE Paiement ADD CONSTRAINT FK_48AA18487F2DEE08 FOREIGN KEY (facture_id) REFERENCES Facture (id)');
        $this->addSql('ALTER TABLE Paiement RENAME INDEX fk_paiement_facture TO IDX_48AA18487F2DEE08');
        $this->addSql('ALTER TABLE ProfilMedical DROP FOREIGN KEY `ProfilMedical_ibfk_1`');
        $this->addSql('ALTER TABLE ProfilMedical CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titulaire_id titulaire_id INT NOT NULL, CHANGE contact_urgence contact_urgence VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ProfilMedical ADD CONSTRAINT FK_83D41BE2A10273AA FOREIGN KEY (titulaire_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE ProfilMedical RENAME INDEX titulaire_id TO IDX_83D41BE2A10273AA');
        $this->addSql('ALTER TABLE RapportMedical DROP FOREIGN KEY `RapportMedical_ibfk_1`');
        $this->addSql('ALTER TABLE RapportMedical DROP FOREIGN KEY `RapportMedical_ibfk_2`');
        $this->addSql('DROP INDEX consultation_id ON RapportMedical');
        $this->addSql('ALTER TABLE RapportMedical DROP consultation_id, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE dossier_id dossier_id INT NOT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE conclusion conclusion LONGTEXT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE RapportMedical ADD CONSTRAINT FK_ED7A6C57611C0C56 FOREIGN KEY (dossier_id) REFERENCES DossierClinique (id)');
        $this->addSql('ALTER TABLE RapportMedical RENAME INDEX dossier_id TO IDX_ED7A6C57611C0C56');
        $this->addSql('ALTER TABLE RendezVous DROP FOREIGN KEY `RendezVous_ibfk_1`');
        $this->addSql('ALTER TABLE RendezVous CHANGE profil_id profil_id INT NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE type type VARCHAR(100) NOT NULL, CHANGE motif motif VARCHAR(60) DEFAULT NULL, CHANGE medecin_id medecin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE RendezVous ADD CONSTRAINT FK_2FF53746275ED078 FOREIGN KEY (profil_id) REFERENCES ProfilMedical (id)');
        $this->addSql('ALTER TABLE RendezVous ADD CONSTRAINT FK_2FF537464F31A84 FOREIGN KEY (medecin_id) REFERENCES Utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2FF537464F31A84 ON RendezVous (medecin_id)');
        $this->addSql('ALTER TABLE RendezVous RENAME INDEX profil_id TO IDX_2FF53746275ED078');
        $this->addSql('ALTER TABLE Specialite DROP FOREIGN KEY `Specialite_ibfk_1`');
        $this->addSql('ALTER TABLE Specialite CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE couleur couleur VARCHAR(20) DEFAULT NULL, CHANGE departement_id departement_id INT NOT NULL');
        $this->addSql('ALTER TABLE Specialite ADD CONSTRAINT FK_A88BFF11CCF9E01E FOREIGN KEY (departement_id) REFERENCES Departement (id)');
        $this->addSql('ALTER TABLE Specialite RENAME INDEX departement_id TO IDX_A88BFF11CCF9E01E');
        $this->addSql('ALTER TABLE Utilisateur DROP FOREIGN KEY `Utilisateur_ibfk_1`');
        $this->addSql('ALTER TABLE Utilisateur CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE roles roles JSON NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE type_utilisateur type_utilisateur VARCHAR(255) NOT NULL, CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE specialite_id specialite_id INT DEFAULT NULL, CHANGE is_verified is_verified TINYINT NOT NULL');
        $this->addSql('ALTER TABLE Utilisateur ADD CONSTRAINT FK_9B80EC642195E0F0 FOREIGN KEY (specialite_id) REFERENCES Specialite (id)');
        $this->addSql('ALTER TABLE Utilisateur RENAME INDEX email TO UNIQ_9B80EC64E7927C74');
        $this->addSql('ALTER TABLE Utilisateur RENAME INDEX specialite_id TO IDX_9B80EC642195E0F0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ResetPasswordRequest DROP FOREIGN KEY FK_35370143A76ED395');
        $this->addSql('DROP TABLE ResetPasswordRequest');
        $this->addSql('ALTER TABLE ConstanteVitale DROP FOREIGN KEY FK_731B9D8162FF6CDF');
        $this->addSql('DROP INDEX IDX_731B9D8162FF6CDF ON ConstanteVitale');
        $this->addSql('ALTER TABLE Consultation CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE date_effectuee date_effectuee DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE statut statut VARCHAR(50) DEFAULT \'EN_COURS\', CHANGE notes_privees notes_privees TEXT DEFAULT NULL, CHANGE rdv_id rdv_id BIGINT DEFAULT NULL, CHANGE medecin_id medecin_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE Consultation RENAME INDEX idx_8e775e5e4f31a84 TO medecin_id');
        $this->addSql('ALTER TABLE Departement CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE code code VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX code ON Departement (code)');
        $this->addSql('ALTER TABLE Disponibilite DROP FOREIGN KEY FK_9FC485DA4F31A84');
        $this->addSql('ALTER TABLE Disponibilite CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE jour_semaine jour_semaine INT DEFAULT NULL, CHANGE est_recurrent est_recurrent TINYINT DEFAULT 1, CHANGE medecin_id medecin_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE Disponibilite ADD CONSTRAINT `Disponibilite_ibfk_1` FOREIGN KEY (medecin_id) REFERENCES Utilisateur (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Disponibilite RENAME INDEX idx_9fc485da4f31a84 TO medecin_id');
        $this->addSql('ALTER TABLE DossierClinique DROP FOREIGN KEY FK_45853F19275ED078');
        $this->addSql('ALTER TABLE DossierClinique CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE antecedents antecedents TEXT DEFAULT NULL, CHANGE profil_id profil_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE DossierClinique ADD CONSTRAINT `DossierClinique_ibfk_1` FOREIGN KEY (profil_id) REFERENCES ProfilMedical (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE DossierClinique RENAME INDEX uniq_45853f19275ed078 TO profil_id');
        $this->addSql('ALTER TABLE Facture DROP INDEX UNIQ_313B5D8C62FF6CDF, ADD INDEX fk_facture_consultation (consultation_id)');
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8C62FF6CDF');
        $this->addSql('ALTER TABLE Facture CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE consultation_id consultation_id BIGINT NOT NULL, CHANGE titulaire_id titulaire_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT `fk_facture_consultation` FOREIGN KEY (consultation_id) REFERENCES Consultation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Facture RENAME INDEX idx_313b5d8ca10273aa TO fk_facture_titulaire');
        $this->addSql('ALTER TABLE Paiement DROP FOREIGN KEY FK_48AA18487F2DEE08');
        $this->addSql('ALTER TABLE Paiement CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE facture_id facture_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE Paiement ADD CONSTRAINT `fk_paiement_facture` FOREIGN KEY (facture_id) REFERENCES Facture (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Paiement RENAME INDEX idx_48aa18487f2dee08 TO fk_paiement_facture');
        $this->addSql('ALTER TABLE ProfilMedical DROP FOREIGN KEY FK_83D41BE2A10273AA');
        $this->addSql('ALTER TABLE ProfilMedical CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE contact_urgence contact_urgence VARCHAR(255) DEFAULT NULL, CHANGE titulaire_id titulaire_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE ProfilMedical ADD CONSTRAINT `ProfilMedical_ibfk_1` FOREIGN KEY (titulaire_id) REFERENCES Utilisateur (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ProfilMedical RENAME INDEX idx_83d41be2a10273aa TO titulaire_id');
        $this->addSql('ALTER TABLE RapportMedical DROP FOREIGN KEY FK_ED7A6C57611C0C56');
        $this->addSql('ALTER TABLE RapportMedical ADD consultation_id BIGINT DEFAULT NULL, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE contenu contenu TEXT DEFAULT NULL, CHANGE conclusion conclusion TEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE dossier_id dossier_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE RapportMedical ADD CONSTRAINT `RapportMedical_ibfk_1` FOREIGN KEY (consultation_id) REFERENCES Consultation (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE RapportMedical ADD CONSTRAINT `RapportMedical_ibfk_2` FOREIGN KEY (dossier_id) REFERENCES DossierClinique (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX consultation_id ON RapportMedical (consultation_id)');
        $this->addSql('ALTER TABLE RapportMedical RENAME INDEX idx_ed7a6c57611c0c56 TO dossier_id');
        $this->addSql('ALTER TABLE RendezVous DROP FOREIGN KEY FK_2FF53746275ED078');
        $this->addSql('ALTER TABLE RendezVous DROP FOREIGN KEY FK_2FF537464F31A84');
        $this->addSql('DROP INDEX IDX_2FF537464F31A84 ON RendezVous');
        $this->addSql('ALTER TABLE RendezVous CHANGE statut statut VARCHAR(50) DEFAULT \'PLANIFIE\', CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE motif motif VARCHAR(60) NOT NULL, CHANGE profil_id profil_id BIGINT NOT NULL, CHANGE medecin_id medecin_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE RendezVous ADD CONSTRAINT `RendezVous_ibfk_1` FOREIGN KEY (profil_id) REFERENCES ProfilMedical (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE RendezVous RENAME INDEX idx_2ff53746275ed078 TO profil_id');
        $this->addSql('ALTER TABLE Specialite DROP FOREIGN KEY FK_A88BFF11CCF9E01E');
        $this->addSql('ALTER TABLE Specialite CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE couleur couleur VARCHAR(7) DEFAULT NULL, CHANGE departement_id departement_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE Specialite ADD CONSTRAINT `Specialite_ibfk_1` FOREIGN KEY (departement_id) REFERENCES Departement (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE Specialite RENAME INDEX idx_a88bff11ccf9e01e TO departement_id');
        $this->addSql('ALTER TABLE Utilisateur DROP FOREIGN KEY FK_9B80EC642195E0F0');
        $this->addSql('ALTER TABLE Utilisateur CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE roles roles JSON DEFAULT NULL, CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE prenom prenom VARCHAR(100) DEFAULT NULL, CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE is_verified is_verified TINYINT DEFAULT 0 NOT NULL, CHANGE type_utilisateur type_utilisateur ENUM(\'ADMIN\', \'TITULAIRE\', \'PERSONNEL\', \'MEDECIN\') NOT NULL, CHANGE specialite_id specialite_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE Utilisateur ADD CONSTRAINT `Utilisateur_ibfk_1` FOREIGN KEY (specialite_id) REFERENCES Specialite (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE Utilisateur RENAME INDEX uniq_9b80ec64e7927c74 TO email');
        $this->addSql('ALTER TABLE Utilisateur RENAME INDEX idx_9b80ec642195e0f0 TO specialite_id');
    }
}
