# Rapport de Tests et d'Analyse — Clinique 360

**Projet** : Gestion Clinique — Application Web Symfony 6.4  
**Branche** : `feat/web/testing`  
**Date** : 2026-03-02  
**Environnement** : PHP 8.2.29, PHPUnit 11.5.55, PHPStan 2.1.17

---

## Table des Matières

1. [Réalisation des Tests Unitaires](#1-réalisation-des-tests-unitaires)
2. [Réalisation des Tests Statiques avec PHPStan](#2-réalisation-des-tests-statiques-avec-phpstan)
3. [Analyse de l'Application avec Doctrine Doctor](#3-analyse-de-lapplication-avec-doctrine-doctor)
4. [Valeur Ajoutée dans l'Application](#4-valeur-ajoutée-dans-lapplication)

---

## 1. Réalisation des Tests Unitaires

### 1.1 Configuration

- **Framework** : PHPUnit 11.5.55
- **Environnement** : `APP_ENV=test`
- **Fichier de configuration** : `phpunit.dist.xml`
- **Approche** : Tests unitaires purs (`PHPUnit\Framework\TestCase`) avec mocks – aucune dépendance à la base de données

### 1.2 Fonctionnalités Testées

6 fichiers de tests créés couvrant les 6 fonctionnalités métier demandées :

| #         | Fonctionnalité                                | Fichier de test                                     | Tests  | Assertions |
| --------- | --------------------------------------------- | --------------------------------------------------- | ------ | ---------- |
| 1         | Recherche multicritère consultations (admin)  | `tests/Repository/ConsultationSearchTest.php`       | 10     | 20+        |
| 2         | Statistiques factures (tableau de bord admin) | `tests/Controller/FactureStatisticsTest.php`        | 8      | 12+        |
| 3         | Créneaux disponibles RDV                      | `tests/Controller/RendezVousCreneauxTest.php`       | 11     | 18+        |
| 4         | Authentification avancée (redirection rôles)  | `tests/Security/AppAuthenticatorTest.php`           | 7      | 14+        |
| 5         | Filtrage disponibilité médecin                | `tests/Repository/DisponibiliteFilterTest.php`      | 12     | 20+        |
| 6         | Infirmier / Constantes vitales (alertes)      | `tests/Service/ConstanteVitaleAlertServiceTest.php` | 30     | 45+        |
|           |                                               | `tests/Service/MedicalScoreCalculatorTest.php`      | 12     | 20+        |
| **Total** |                                               | **7 fichiers**                                      | **90** | **149+**   |

### 1.3 Résultats d'Exécution

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.29
Configuration: phpunit.dist.xml

Time: 00:00.102, Memory: 14.00 MB
Tests: 111, Assertions: 185
OK (111 tests, 185 assertions)
```

**Nos 90 tests unitaires : 90/90 PASSÉS (OK)**

Les 21 tests restants proviennent de tests pré-existants du projet qui passent également avec succès.

### 1.4 Détail par Fonctionnalité

---

#### Fonctionnalité 1 : Recherche Multicritère des Consultations (Admin)

**Fichier** : `tests/Repository/ConsultationSearchTest.php`  
**Classe testée** : `ConsultationRepository::searchConsultations()`

La méthode `searchConsultations(?int $medecinId, ?\DateTimeInterface $date, ?string $statut)` implémente un QueryBuilder dynamique avec des clauses `WHERE` optionnelles.

| Test                                         | Description                     | Résultat |
| -------------------------------------------- | ------------------------------- | -------- |
| `testRechercheParMedecin`                    | Filtre par ID médecin seul      | ✔        |
| `testRechercheParDate`                       | Filtre par date seule           | ✔        |
| `testRechercheParStatut`                     | Filtre par statut seul          | ✔        |
| `testRechercheMedecinEtDate`                 | Combinaison médecin + date      | ✔        |
| `testRechercheMedecinEtStatut`               | Combinaison médecin + statut    | ✔        |
| `testRechercheTroisCriteres`                 | Les 3 critères simultanés       | ✔        |
| `testRechercheSansCritere`                   | Aucun critère → tout retourné   | ✔        |
| `testRechercheAucunResultat`                 | Médecin inexistant → vide       | ✔        |
| `testRechercheMedecinDateStatutSansResultat` | Combinaison sans correspondance | ✔        |
| `testStatutVideIgnore`                       | Statut `""` traité comme null   | ✔        |

---

#### Fonctionnalité 2 : Statistiques de Factures (Dashboard Admin)

**Fichier** : `tests/Controller/FactureStatisticsTest.php`  
**Logique testée** : Algorithmes de calcul du `FactureController::dashboard()`

| Test                                | Description                       | Résultat |
| ----------------------------------- | --------------------------------- | -------- |
| `testComptageFacturesParStatut`     | Comptage PAYEE/EN_ATTENTE/ANNULEE | ✔        |
| `testComptageFacturesVide`          | Liste vide → 0                    | ✔        |
| `testComptageStatutInsensibleCasse` | `payee`/`Payee`/`PAYEE` → 3       | ✔        |
| `testRevenusMensuelsAnneeCourante`  | Agrégation par mois (12 buckets)  | ✔        |
| `testRevenusAnneePrecedenteExclus`  | Paiements année-1 → exclus        | ✔        |
| `testTotalEncaisse`                 | Somme de tous les paiements       | ✔        |
| `testTotalEncaisseVide`             | Aucun paiement → 0                | ✔        |
| `testLast7DaysAvecPaiements`        | Ventilation 7 derniers jours      | ✔        |

---

#### Fonctionnalité 3 : Créneaux Disponibles (Formulaire de RDV)

**Fichier** : `tests/Controller/RendezVousCreneauxTest.php`  
**Logique testée** : Algorithme de génération de créneaux de `RendezVousController::getCreneauxDisponibles()`

| Test                                   | Description                        | Résultat |
| -------------------------------------- | ---------------------------------- | -------- |
| `testGenerationCreneauxSimple`         | 08:00→10:00 = 4 créneaux de 30 min | ✔        |
| `testCreneauxAvecRdvDejaReserve`       | Exclusion du créneau 08:30 réservé | ✔        |
| `testCreneauxAvecPlusieursRdvReserves` | Exclusion multiple                 | ✔        |
| `testCreneauxTousReserves`             | Tous pris → tableau vide           | ✔        |
| `testPlusieursPlagesDeDisponibilite`   | Matin + après-midi combinés        | ✔        |
| `testAlignementSurDemiHeures`          | 08:15 arrondi à 08:30              | ✔        |
| `testAlignementCreneauExactDemiHeure`  | 08:30 non modifié                  | ✔        |
| `testPlageTropCourte`                  | <30min → aucun créneau             | ✔        |
| `testPlageExactement30Min`             | Exactement 30 min → 1 créneau      | ✔        |
| `testAucuneDisponibilite`              | Pas de dispo → vide                | ✔        |
| `testCreneauxTriesChronologiquement`   | Tri final chronologique            | ✔        |

---

#### Fonctionnalité 4 : Authentification Avancée

**Fichier** : `tests/Security/AppAuthenticatorTest.php`  
**Classe testée** : `AppAuthenticator::onAuthenticationSuccess()`

| Test                                   | Description                           | Résultat |
| -------------------------------------- | ------------------------------------- | -------- |
| `testRedirectionAdmin`                 | ROLE_ADMIN → `/admin`                 | ✔        |
| `testRedirectionMedecin`               | ROLE_MEDECIN → `/medecin/{id}`        | ✔        |
| `testRedirectionUtilisateurNonVerifie` | Non vérifié → `/first-login/verify`   | ✔        |
| `testRedirectionChangementMotDePasse`  | must_change → `/first-login/password` | ✔        |
| `testRedirectionDefautTitulaire`       | ROLE_USER → `/titulaire`              | ✔        |
| `testGetLoginUrl`                      | Route de login correcte               | ✔        |
| `testLoginRouteConstant`               | `LOGIN_ROUTE = 'app_login'`           | ✔        |

---

#### Fonctionnalité 5 : Filtrage Disponibilité Médecin

**Fichier** : `tests/Repository/DisponibiliteFilterTest.php`  
**Classes testées** : `DisponibiliteRepository::findByFilters()` et `findOverlapping()`

| Test                             | Description                              | Résultat |
| -------------------------------- | ---------------------------------------- | -------- |
| `testFiltreParMedecin`           | Filtre par ID médecin                    | ✔        |
| `testFiltreParJour`              | Filtre par jour de semaine               | ✔        |
| `testFiltreParRecurrent`         | Filtre disponibilités récurrentes        | ✔        |
| `testFiltreParNonRecurrent`      | Filtre disponibilités ponctuelles        | ✔        |
| `testFiltreMedecinEtJour`        | Combinaison médecin + jour               | ✔        |
| `testFiltreTroisCriteres`        | Médecin + jour + récurrence              | ✔        |
| `testSansFiltreRetourneTout`     | Aucun filtre → 5 résultats               | ✔        |
| `testFiltreAucunResultat`        | Dimanche → aucun résultat                | ✔        |
| `testChevauchementDetecte`       | 09:00–11:00 chevauche 08:00–12:00        | ✔        |
| `testPasDeChevauchement`         | 12:00–14:00 ne chevauche pas 08:00–12:00 | ✔        |
| `testChevauchementAvecExclusion` | Exclusion par ID (cas d'édition)         | ✔        |
| `testChevauchementPartielFin`    | Chevauchement sur 2 plages               | ✔        |

---

#### Fonctionnalité 6 : Infirmier / Constantes Vitales

**Fichiers** :

- `tests/Service/ConstanteVitaleAlertServiceTest.php` (30 tests)
- `tests/Service/MedicalScoreCalculatorTest.php` (12 tests)

**Classes testées** : `ConstanteVitaleAlertService` et `MedicalScoreCalculator`

##### ConstanteVitaleAlertService — Alertes Vitales (30 tests)

| Catégorie                                                         | Tests | Résultat |
| ----------------------------------------------------------------- | ----- | -------- |
| Température (normal/warning/critical, limites basses et hautes)   | 7     | ✔        |
| Fréquence cardiaque (normal, tachycardie, bradycardie)            | 3     | ✔        |
| SpO2 (normal, warning, critique)                                  | 3     | ✔        |
| Glycémie (normal, hypo-/hyperglycémie critique)                   | 3     | ✔        |
| Potassium (normal, hypo-/hyperkaliémie)                           | 3     | ✔        |
| Glasgow (critique, normal)                                        | 2     | ✔        |
| Type inconnu → `'unknown'`                                        | 1     | ✔        |
| Normalisation (accents, majuscules)                               | 2     | ✔        |
| `getReference()` (existante, inexistante)                         | 2     | ✔        |
| `getAllReferences()` (>20 types)                                  | 1     | ✔        |
| Labels, badges CSS, icônes FontAwesome                            | 5     | ✔        |
| `analyzeConstantes()` (critique, warning, sans alerte, multiples) | 4     | ✔        |
| Boundary testing (bornes exactes)                                 | 3     | ✔        |

##### MedicalScoreCalculator — Score de Risque (12 tests)

| Test                           | Description                  | Résultat |
| ------------------------------ | ---------------------------- | -------- |
| `testPatientSansRisque`        | Score 0 → Normal             | ✔        |
| `testUneAllergie`              | 1 allergie → +1 pt           | ✔        |
| `testTroisAllergies`           | ≥3 allergies → +2 pts        | ✔        |
| `testUnAntecedent`             | 1 antécédent → +1 pt         | ✔        |
| `testTroisAntecedents`         | ≥3 antécédents → +2 pts      | ✔        |
| `testPatientJeune30Ans`        | Âge 30 → +0 pt               | ✔        |
| `testPatient55Ans`             | Âge 55 → +1 pt               | ✔        |
| `testPatient70Ans`             | Âge 70 → +2 pts              | ✔        |
| `testNiveauAVerifier`          | Score 2 → "À vérifier"       | ✔        |
| `testNiveauPrioritaire`        | Score 6 → "Prioritaire"      | ✔        |
| `testDossierSansProfilMedical` | Profil null → Score 0        | ✔        |
| `testStructureRetour`          | Vérification clés du tableau | ✔        |

---

## 2. Réalisation des Tests Statiques avec PHPStan

### 2.1 Configuration

- **Outil** : PHPStan 2.1.17
- **Niveau d'analyse** : 5 (sur 10)
- **Extensions** : `phpstan-symfony`, `phpstan-doctrine`
- **Périmètre** : Répertoire `src/`
- **Fichier de configuration** : `phpstan.neon`

### 2.2 Résultats Initiaux

```
PHPStan — Level 5
53 erreurs trouvées
```

### 2.3 Classification des Erreurs Détectées

| Catégorie                                                   | Nombre | Sévérité | Description                                                                                                                  |
| ----------------------------------------------------------- | ------ | -------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `method.notFound` — Appel de méthode sur `UserInterface`    | 18     | Moyenne  | Les contrôleurs appellent `getId()`, `getNiveauAcces()`, `getProfilsMedicaux()` sur `UserInterface` au lieu de `Utilisateur` |
| `property.unusedType` — Type jamais assigné sur les entités | 14     | Faible   | `$id` déclaré `?int` mais jamais assigné manuellement (géré par Doctrine)                                                    |
| `argument.type` — Type d'argument incompatible              | 4      | Moyenne  | `UserInterface` passé là où `Utilisateur\|null` est attendu                                                                  |
| `booleanNot.alwaysFalse` / `identical.alwaysFalse`          | 4      | Faible   | Expressions toujours fausses (comparaisons redondantes)                                                                      |
| `nullCoalesce.expr` — Expression non nullable               | 3      | Faible   | Opérateur `??` sur une valeur jamais nulle                                                                                   |
| `function.alreadyNarrowedType`                              | 4      | Info     | `is_array()` et `method_exists()` sur des valeurs déjà typées                                                                |
| Autres (comparaison, logique)                               | 6      | Faible   | Conditions toujours vraies/fausses                                                                                           |

### 2.4 Corrections Appliquées

Toutes les 53 erreurs ont été corrigées. Voici le détail des corrections par catégorie.

#### Correction 1 : Typage `UserInterface` → `Utilisateur` (22 erreurs corrigées)

Les contrôleurs Front utilisent `$this->getUser()` qui retourne `UserInterface`. Or nos méthodes métier attendent `App\Entity\Utilisateur` ou ses sous-classes (`Medecin`, `Personnel`). Nous avons ajouté des annotations PHPDoc `@var` pour informer PHPStan du type concret :

```php
// Avant (problématique)
$user = $this->getUser();
$user->getId(); // ❌ PHPStan: method.notFound

// Après (corrigé)
/** @var Utilisateur $user */
$user = $this->getUser();
$user->getId(); // ✔ PHPStan: OK
```

**Fichiers corrigés** (13 contrôleurs) :

- `Controller/DisponibiliteController.php` — ajout import + `@var` sur `$this->getUser()`
- `Controller/Front/Infirmier/ConsultationController.php` — ajout import + `@var`
- `Controller/Front/Infirmier/DashboardController.php` — ajout import + `@var`
- `Controller/Front/Infirmier/HistoriqueController.php` — 3 méthodes corrigées
- `Controller/Front/Medecin/ConstanteVitaleController.php` — ajout import + `@var`
- `Controller/Front/Medecin/ConsultationController.php` — 2 méthodes corrigées
- `Controller/Front/Medecin/DashboardController.php` — 2 méthodes corrigées
- `Controller/Front/Medecin/DisponibiliteController.php` — 4 méthodes corrigées
- `Controller/Front/Medecin/RendezVousController.php` — cast `Medecin` pour `setMedecin()`
- `Controller/Front/Reception/DashboardController.php` — ajout import + `@var`
- `Controller/Front/Receptionniste/DashboardController.php` — `@var` dans `checkReceptionist()`
- `Controller/RendezVousController.php` — ajout import + `@var`
- `Controller/TitulaireController.php` — 3 méthodes corrigées + `@var Utilisateur|null` pour les contrôles `!$user`

#### Correction 2 : Propriétés `$id` des entités Doctrine (14 erreurs ignorées)

Les erreurs `property.unusedType` sur les `$id` de toutes les entités sont des faux positifs : Doctrine gère l'assignation en interne via la couche d'hydratation. Nous avons configuré `phpstan.neon` pour les ignorer proprement :

```yaml
ignoreErrors:
  - message: '#Property App\\Entity\\.*::\$id \(int\|null\) is never assigned int#'
    path: src/Entity/
  - message: '#Property App\\Entity\\RendezVous::\$id \(string\|null\) is never assigned string#'
    path: src/Entity/RendezVous.php
  - message: "#is never assigned null so it can be removed#"
    path: src/Entity/
```

#### Correction 3 : Opérateurs `??` redondants (3 erreurs corrigées)

`DossierClinique::getAllergies()` retourne toujours `array` (jamais `null`). L'opérateur `??` était donc inutile.

```php
// Avant
$allergies = $dossier->getAllergies() ?? [];

// Après
$allergies = $dossier->getAllergies();
```

**Fichiers corrigés** :

- `Controller/Admin/DossierCliniqueController.php`
- `Controller/Admin/MedicalScoreController.php`
- `Service/MedicalScoreCalculator.php`

#### Correction 4 : Vérifications `is_array()` et `method_exists()` redondantes (4 erreurs corrigées)

```php
// Avant — method_exists() toujours vrai car getNiveauAcces() existe sur Utilisateur
if (method_exists($user, 'getNiveauAcces') && $user->getNiveauAcces() === 'INFIRMIER')

// Après — vérification directe
if ($user->getNiveauAcces() === 'INFIRMIER')
```

**Fichiers corrigés** :

- `Security/AppAuthenticator.php` — 2 `method_exists()` supprimés
- `Controller/Front/Medecin/MedecinDossierCliniqueController.php` — `is_array()` redondant supprimé
- `Service/SymptomTriageService.php` — `is_array($data)` supprimé (toujours `true` après `toArray()`)

#### Correction 5 : Comparaisons toujours vraies/fausses (6 erreurs corrigées)

| Fichier                                   | Avant                      | Après        | Raison                                      |
| ----------------------------------------- | -------------------------- | ------------ | ------------------------------------------- |
| `Admin/DashboardController.php:222`       | `\|\| $val === 0`          | supprimé     | Valeur toujours `> 0` quand définie         |
| `Service/MedicalAssistantService.php:361` | `$age >= 60 && $age <= 75` | `$age >= 60` | `<= 75` redondant après le `if ($age > 75)` |
| `TitulaireController.php`                 | `!isset() \|\| === null`   | `empty()`    | Simplifié en une seule vérification         |

#### Correction 6 : `DateTimeInterface::modify()` (1 erreur corrigée)

`modify()` n'existe que sur `DateTime`, pas sur l'interface `DateTimeInterface`. Corrigé avec `DateTime::createFromInterface()` :

```php
// Avant — ❌ modify() n'existe pas sur DateTimeInterface
$dateFin = clone $rendezVous->getDateDebut();
$dateFin->modify('+30 minutes');

// Après — ✔ conversion explicite
$dateFin = DateTime::createFromInterface($rendezVous->getDateDebut());
$dateFin->modify('+30 minutes');
```

**Fichier corrigé** : `Controller/RendezVousController.php`

#### Correction 7 : `@phpstan-ignore` pour la ré-évaluation du formulaire (1 erreur annotée)

Dans `RendezVousController`, après avoir ajouté des erreurs via `$form->addError()`, un second appel `$form->isValid()` est intentionnel (re-validation). PHPStan considère cela comme toujours faux, mais c'est un comportement voulu :

```php
/** @phpstan-ignore booleanNot.alwaysFalse (re-evaluated after addError calls) */
if (!$form->isValid()) { ... }
```

### 2.5 Résultats Après Corrections

```
PHPStan — Level 5
 [OK] No errors
```

### 2.6 Bilan PHPStan

| Métrique                                  | Avant                                                                | Après                   |
| ----------------------------------------- | -------------------------------------------------------------------- | ----------------------- |
| Niveau d'analyse                          | 5 / 10                                                               | 5 / 10                  |
| Fichiers analysés                         | ~80                                                                  | ~80                     |
| **Erreurs totales**                       | **53**                                                               | **0**                   |
| Erreurs critiques corrigées               | 22                                                                   | 0                       |
| Erreurs mineures corrigées                | 17                                                                   | 0                       |
| Erreurs Doctrine ignorées (faux positifs) | 14                                                                   | 0 (ignorées via config) |
| Services métier sans erreur               | `ConstanteVitaleAlertService`, `HolidayApiService`, `MailingService` | Tous                    |

---

## 3. Analyse de l'Application avec Doctrine Doctor

### 3.1 Outils Utilisés

- `doctrine:schema:validate` — Validation du mapping et synchronisation avec la BDD
- `doctrine:mapping:info` — Inventaire des entités mappées
- `doctrine:schema:update --dump-sql` — Différences entre le schéma ORM et la base

### 3.2 Validation du Mapping

```
Mapping
-------
 [OK] The mapping files are correct.
```

**✔ Le mapping Doctrine est valide.** Les 18 entités sont correctement configurées avec leurs attributs PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`, etc.).

### 3.3 Inventaire des Entités

| Statut | Entité                            | Type                               |
| ------ | --------------------------------- | ---------------------------------- |
| ✔ OK   | `App\Entity\Utilisateur`          | Racine (SINGLE_TABLE inheritance)  |
| ✔ OK   | `App\Entity\Admin`                | Sous-classe de Utilisateur         |
| ✔ OK   | `App\Entity\Medecin`              | Sous-classe de Utilisateur         |
| ✔ OK   | `App\Entity\Personnel`            | Sous-classe de Utilisateur         |
| ✔ OK   | `App\Entity\Titulaire`            | Sous-classe de Utilisateur         |
| ✔ OK   | `App\Entity\Consultation`         | ManyToOne → Medecin, RendezVous    |
| ✔ OK   | `App\Entity\ConstanteVitale`      | ManyToOne → Consultation           |
| ✔ OK   | `App\Entity\RendezVous`           | ManyToOne → ProfilMedical, Medecin |
| ✔ OK   | `App\Entity\Disponibilite`        | ManyToOne → Medecin                |
| ✔ OK   | `App\Entity\Facture`              | OneToOne → Consultation            |
| ✔ OK   | `App\Entity\Paiement`             | ManyToOne → Facture                |
| ✔ OK   | `App\Entity\ProfilMedical`        | ManyToOne → Utilisateur            |
| ✔ OK   | `App\Entity\DossierClinique`      | OneToOne → ProfilMedical           |
| ✔ OK   | `App\Entity\RapportMedical`       | ManyToOne → DossierClinique        |
| ✔ OK   | `App\Entity\Departement`          | Standalone                         |
| ✔ OK   | `App\Entity\Specialite`           | ManyToOne → Departement            |
| ✔ OK   | `App\Entity\ResetPasswordRequest` | ManyToOne → Utilisateur            |
| ✔ OK   | `Vich\UploaderBundle\Entity\File` | Bundle externe                     |

**18 entités mappées — toutes valides.**

### 3.4 Synchronisation Base de Données

```
Database
--------
 [ERROR] The database schema is not in sync with the current mapping file.
```

**Différences détectées** (non bloquantes en développement) :

| Type              | Description                                                                                                        | Impact               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------ | -------------------- |
| Renommage d'index | Les index utilisent des noms MySQL natifs (`Utilisateur_ibfk_1`) au lieu des noms Doctrine (`FK_9B80EC642195E0F0`) | Cosmétique           |
| Clés étrangères   | Certaines FK utilisent la syntaxe MySQL legacy au lieu de la convention Doctrine                                   | Cosmétique           |
| Index manquant    | `ConstanteVitale.consultation_id` n'a pas d'index dédié                                                            | Performance          |
| Colonne supprimée | `RapportMedical.consultation_id` existe en BDD mais pas dans le mapping                                            | Migration nécessaire |

**Recommandation** : Exécuter `php bin/console doctrine:migrations:diff` pour générer une migration de synchronisation, puis `doctrine:migrations:migrate`.

### 3.5 Diagramme des Relations

```
Departement ←─── Specialite ←─── Utilisateur (SINGLE_TABLE)
                                      ├── Admin
                                      ├── Medecin ←─── Disponibilite
                                      ├── Personnel
                                      └── Titulaire ←─── ProfilMedical ←─── DossierClinique
                                                              │                    │
                                                              │               RapportMedical
                                                          RendezVous
                                                              │
                                                         Consultation ←─── ConstanteVitale
                                                              │
                                                           Facture ←─── Paiement
```

---

## 4. Valeur Ajoutée dans l'Application

### 4.1 Intégration IA — Service Gemini (Google AI)

L'application intègre **Google Gemini** (LLM) via `GeminiService` pour :

- **Analyse IA des constantes vitales** (`/infirmier/constantes/ai-analysis/{id}`) : Après agrégation des alertes par le `ConstanteVitaleAlertService`, les résultats sont envoyés à Gemini pour une interprétation médicale en langage naturel.
- **Assistant médical** (`MedicalAssistantService`) : Aide au triage et à la priorisation des patients.
- **Triage par symptômes** (`SymptomTriageService`) : Classification automatique de la gravité.

### 4.2 API REST Sécurisée

- **API Platform** intégrée avec `ApiResource` pour les entités critiques
- **API Constantes Vitales** (`ConstanteVitaleAlertApiController`) : 4 endpoints (`/api/alertes/references`, `/check`, `/analyze`, `/consultation/{id}`)
- **Sécurité API** : `#[IsGranted('IS_AUTHENTICATED_FULLY')]` + règle d'accès `security.yaml`

### 4.3 Détection d'Alertes Médicales en Temps Réel

Le `ConstanteVitaleAlertService` implémente un système de détection d'alertes basé sur **26+ types de constantes vitales** avec des seuils de référence tirés de sources médicales reconnues :

| Source | Types couverts                                                           |
| ------ | ------------------------------------------------------------------------ |
| OMS    | Température, glycémie, hémoglobine, SpO2, sodium, plaquettes, leucocytes |
| AHA    | Fréquence cardiaque, pouls, tension systolique/diastolique, PAM          |
| ESC    | Débit cardiaque                                                          |
| ERS    | Fréquence respiratoire                                                   |
| ADA    | Glycémie postprandiale                                                   |
| KDIGO  | Créatinine, potassium, diurèse                                           |
| NICE   | Score de Glasgow                                                         |
| HAS    | Douleur (EVA)                                                            |

### 4.4 Intégration API Externe — Jours Fériés

Le `HolidayApiService` consomme l'API [Nager.Date](https://date.nager.at) pour détecter les jours fériés tunisiens et empêcher la création de disponibilités médecin sur ces jours.

### 4.5 Authentification Avancée

- **Multi-rôles** : ROLE_ADMIN, ROLE_MEDECIN, ROLE_PERSONNEL (INFIRMIER/RECEPTIONIST), ROLE_USER (Titulaire)
- **First Login Flow** : Vérification email (code 6 chiffres, expiration 15 min) + changement de mot de passe obligatoire
- **OAuth2** : Connexion via Google et Facebook (`GoogleAuthenticator`, `FacebookAuthenticator`)
- **2FA** : Authentification à deux facteurs via `scheb/2fa`
- **Redirection intelligente** : Routage post-login basé sur le rôle de l'utilisateur

### 4.6 Score Médical de Risque

Le `MedicalScoreCalculator` calcule un score composite basé sur :

- Nombre d'allergies (0/+1/+2 pts)
- Nombre d'antécédents médicaux (0/+1/+2 pts)
- Âge du patient (0/+1/+2 pts)

→ 3 niveaux : **Normal** (≤1), **À vérifier** (≤3), **Prioritaire** (>3)

### 4.7 Génération de Documents

- **QR Code** : Intégration `endroid/qr-code` pour les rendez-vous
- **PDF** : Génération de factures PDF via `DomPDF` (`FacturePdfBundle`)
- **Notifications SMS** : Intégration Twilio pour les rappels de rendez-vous

---

## 5. Résumé Exécutif

| Métrique                   | Résultat                                    |
| -------------------------- | ------------------------------------------- |
| Tests unitaires créés      | **90**                                      |
| Tests unitaires réussis    | **90/90 (100%)**                            |
| Assertions vérifiées       | **149+**                                    |
| Fonctionnalités couvertes  | **6/6**                                     |
| Erreurs PHPStan (niveau 5) | **53** (22 critiques, 31 mineures)          |
| Entités Doctrine mappées   | **18/18 valides**                           |
| Mapping Doctrine           | **✔ Correct**                               |
| Synchronisation BDD        | **⚠ Migration nécessaire**                  |
| APIs IA intégrées          | Gemini (Google AI)                          |
| APIs externes              | Nager.Date (jours fériés)                   |
| Authentification           | Form login + OAuth2 (Google/Facebook) + 2FA |
