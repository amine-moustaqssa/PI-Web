# Guide de Contribution

## 1. Stratégie de Branches (Branching)

Puisque ce projet est un **Monorepo** (Web + Desktop), merci de préfixer vos branches selon la partie du projet sur laquelle vous travaillez :

- **Partie Web :** `feat/web/nom-de-la-tache` ou `fix/web/nom-du-bug`
- **Partie Desktop :** `feat/desktop/nom-de-la-tache`
- **Partie Commune :** `chore/repo-setup` ou `docs/regles`

**Exemples :**

- `feat/web/formulaire-login`
- `fix/desktop/probleme-affichage`

## 2. Messages de Commit

Nous suivons la spécification **Conventional Commits** pour garder un historique propre.

- **Format :** `type(portée): description courte`
- **Types acceptés :**
  - `feat` : Nouvelle fonctionnalité
  - `fix` : Correction de bug
  - `docs` : Documentation uniquement
  - `style` : Formatage (espaces, points-virgules, etc.)
  - `refactor` : Modification du code sans changer le comportement (nettoyage)
  - `chore` : Tâches de maintenance (build, outils, configs)

**Exemples :**

- `feat(web): ajout de l'authentification symfony`
- `fix(desktop): correction du bouton retour`
- `chore(global): mise à jour du gitignore`

## 3. Processus de Pull Request (PR)

1.  **Interdiction de push directement sur la branche `main`.**
2.  Toutes les PR doivent cibler la branche `develop` (ou une branche de feature spécifique).
3.  **Revue de Code :** Vous devez demander la validation d'au moins **un autre membre** de l'équipe avant de fusionner (merge).
4.  **Tests Locaux :** Assurez-vous que le projet compile et tourne correctement sur votre machine avant d'ouvrir la PR.

## 4. Bonnes Pratiques

- Ne committez jamais de fichiers de configuration locaux (comme `.env.local` ou le dossier `/vendor`).
- Écrivez vos descriptions de PR en expliquant **pourquoi** ce changement est nécessaire.
