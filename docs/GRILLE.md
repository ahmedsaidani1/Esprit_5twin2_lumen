# Correspondance avec la grille de validation

Pour chaque critère : ce qui est fait, où le voir dans l'application, et où le trouver dans le code.

## Note individuelle

### Fonctionnalités avancées (2 pts)

| Membre | Fonctionnalité | Où la voir | Code |
|---|---|---|---|
| A | Notifications (demande d'achat, changement de statut, facture) | cloche 🔔 de la barre de menu, `/notifications` | `app/Notifications`, `NotificationController` |
| A | Facture PDF | bouton « Télécharger le PDF » d'une facture | `FacturePdfService`, `factures/pdf.blade.php` |
| A | Facture envoyée par e-mail avec le PDF joint | acceptation d'un échange, ou bouton « Envoyer par e-mail » | `FactureEmise::toMail` |
| B | Graphique de production avec prévision | page d'une installation | `partials/graphique-serie.blade.php` |
| B | Import CSV des productions (erreurs ligne par ligne) | page d'une installation | `ProductionCsvController::import` |
| B | Export CSV des productions et des offres (filtres respectés) | listes Productions et Offres | `ProductionCsvController`, `CsvService` |
| C | Graphique de consommation avec anomalies et prévision | page d'un compteur | `compteurs/show.blade.php` |
| C | Import CSV des relevés (mêmes règles que la saisie) | page d'un compteur | `ConsommationCsvController::import` |
| C | Export CSV des relevés | liste des relevés | `ConsommationCsvController::export` |

### Features IA (3 pts)

Toutes calculées localement, algorithmes écrits en PHP dans `app/Ai` et testés (`tests/Unit/AiAlgorithmsTest.php`).
Explications détaillées : [IA.md](IA.md).

| Membre | Fonctionnalité | Algorithme | Où la voir |
|---|---|---|---|
| A | Risque d'impayé d'une facture, expliqué | Régression logistique (descente de gradient, L2, classes équilibrées), évaluée sur un jeu de test | facture impayée (vue vendeur), colonne « Risque IA » |
| A | Offres recommandées | Recommandation par le contenu (profil d'achat, communauté, fiabilité du vendeur) | tableau de bord, Laboratoire IA |
| B | Prévision de production à 7 jours avec intervalle à 95 % | Lissage exponentiel de Holt, α et β appris par recherche sur grille | page d'une installation |
| B | Offre suggérée (quantité et prix) | Prévision − autoconsommation mesurée, prix selon l'offre et la demande | page d'une installation → « Créer cette offre » |
| C | Détection d'anomalies de consommation | Score z robuste (médiane, MAD) après correction du jour de la semaine | page d'un compteur, alerte sur le tableau de bord |
| C | Prévision de consommation à 30 jours | Régression linéaire multiple (tendance + jours de la semaine) | page d'un compteur |
| C | Profil de consommation et conseils | k-means (k-means++, graine fixe) + règles de conseil, économies estimées | `Consommation → Mon analyse` |

Page récapitulative : **Laboratoire IA** (`/ia`), avec les mesures de qualité de chaque modèle. API : `/api/ia/...`.

## Note de groupe

### Intégration avec Git (1,5 pt)

- Workflow de branches, conventions de commit, revue de code : [CONTRIBUTING.md](../CONTRIBUTING.md)
- Modèle de pull request et d'issues : `.github/pull_request_template.md`, `.github/ISSUE_TEMPLATE/`
- Relecteurs automatiques par zone : `.github/CODEOWNERS`
- Historique en commits par membre et par fonctionnalité (`git log --oneline`)
- CI obligatoire sur chaque pull request

### DevOps (4 pts)

Détails : [DEVOPS.md](DEVOPS.md).

- **Conteneurisation** : `Dockerfile` multi-étapes, `docker-compose.yml` (application, file d'attente, planificateur, MySQL, Mailpit)
- **Intégration continue** : GitHub Actions — style (Pint), analyse statique (Larastan), audit de sécurité, tests sur SQLite et MySQL avec couverture
- **Livraison continue** : image Docker publiée sur GitHub Container Registry (main, develop, tags), test de démarrage de l'image
- **Qualité** : SonarCloud (`sonar-project.properties`), pipeline Jenkins équivalent (`Jenkinsfile`)
- **Supervision** : `/up` (santé), `/metrics` au format Prometheus, Prometheus + tableau de bord Grafana fourni

### Gestion user (3,5 pts)

| Sous-critère | Points | Réalisation | Tests |
|---|---|---|---|
| Authentification de base | 0,5 | **Laravel Fortify** : inscription, connexion, déconnexion ; routes protégées (`auth`, `verified`) ; messages d'erreur en français ; limitation des tentatives | `GestionUtilisateurTest` |
| Reset du mot de passe et vérification d'e-mail | 1 | Lien envoyé par e-mail, **jeton expirant après 60 min**, à usage unique ; `MustVerifyEmail`, lien signé et expirant, renvoi possible | idem |
| Rôles et permissions | 1 | **Spatie Permission** : rôles `admin` / `membre`, 7 permissions ; contrôle **côté serveur** (middleware `permission:`, policies) en plus de l'interface | `UsersAndPermissionsTest`, `GestionUtilisateurTest` |
| 2FA | 0,5 | **Fortify** : activation / désactivation par l'utilisateur, **QR code TOTP**, confirmation par code, **codes de récupération** à usage unique ; aussi exigée par l'API | `GestionUtilisateurTest` |
| Profil utilisateur | 0,5 | Modification des informations et du mot de passe, **photo de profil**, **suppression du compte** (mot de passe demandé) | `GestionUtilisateurTest` |

### Cohérence de l'application (1 pt)

- Une seule interface (Bootstrap), menus regroupés par domaine, textes en français
- Les modules se répondent : la suggestion d'offre (B) utilise le compteur lié (C) ; l'acceptation d'un échange (A) réserve l'offre (B) et émet la facture
- Mêmes règles et autorisations sur le site et l'API (services et policies partagés)
- Validation en HTML et côté serveur, erreurs affichées sous chaque champ
