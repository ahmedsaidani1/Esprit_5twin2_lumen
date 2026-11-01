# ☀ SolarShare

Plateforme web d'**échange d'énergie solaire entre voisins** d'une même communauté : les membres qui produisent
enregistrent leur production et publient des offres, d'autres membres les achètent, l'échange est suivi jusqu'à
la facture. Des fonctionnalités d'**intelligence artificielle** (calculées localement, sans API externe)
prévoient la production et la consommation, détectent les anomalies, estiment le risque d'impayé et recommandent des offres.

Projet du module *Applications Web Avancées* — ESPRIT, 5TWIN2, 2026/2027.

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Lancer le projet](#lancer-le-projet) — avec WAMP ou avec Docker
- [Comptes de démonstration](#comptes-de-démonstration)
- [Tests et qualité](#tests-et-qualité)
- [Architecture](#architecture)
- Documents : [répartition et workflow Git](CONTRIBUTING.md) · [correspondance avec la grille](docs/GRILLE.md) · [DevOps](docs/DEVOPS.md) · [IA](docs/IA.md)

## Fonctionnalités

| | Membre A — échanges et factures | Membre B — installations, productions, offres | Membre C — compteurs et consommations |
|---|---|---|---|
| **CRUD** | Échanges, factures, communautés | Installations, productions, offres | Compteurs, relevés |
| **Avancé** | Notifications, facture PDF, envoi par e-mail | Graphique, import / export CSV | Graphique, import / export CSV |
| **IA** | Risque d'impayé (régression logistique), recommandation d'offres | Prévision de production (Holt), offre suggérée | Anomalies (z-score robuste), prévision (régression), profils (k-means) |

**Commun** : inscription, connexion, mot de passe oublié, vérification d'e-mail et double authentification
(Laravel Fortify) ; rôles et permissions (Spatie) ; profil avec photo et suppression du compte ;
tableau de bord ; API REST sécurisée par jeton (Sanctum) ; filtres dynamiques ; interface en français.

## Lancer le projet

### Option 1 — WAMP (PHP 8.2 ou plus, MySQL)

```powershell
$env:Path = "C:\wamp64\bin\php\php8.3.28;" + $env:Path   # si "php" n'est pas reconnu
composer install
copy .env.example .env          # puis DB_CONNECTION=mysql, DB_DATABASE=lumen, DB_USERNAME=root
php artisan key:generate
php artisan migrate:fresh --seed --seeder=DemoSeeder   # 90 jours de données + entraînement de l'IA
php artisan serve
```

Ouvrir http://127.0.0.1:8000.

**E-mails** (lien de réinitialisation, vérification, factures) : avec `MAIL_MAILER=log`, ils sont écrits dans
`storage/logs/laravel.log`. Pour les voir comme de vrais e-mails, lancer Mailpit
(`docker compose up -d mailpit`), mettre `MAIL_MAILER=smtp`, `MAIL_PORT=1025` dans `.env`, et ouvrir http://localhost:8025.

### Option 2 — Docker (tout inclus)

```bash
php artisan key:generate --show     # copier la valeur dans APP_KEY du fichier .env
docker compose up -d --build        # site : http://localhost:8080 · e-mails : http://localhost:8025
docker compose --profile monitoring up -d   # + Prometheus (9090) et Grafana (3000, admin/admin)
```

Au premier lancement, la base est remplie avec les données de démonstration. Détails : [docs/DEVOPS.md](docs/DEVOPS.md).

## Comptes de démonstration

Mot de passe pour tous : `password`.

| E-mail | Rôle | Profil |
|---|---|---|
| `admin@solarshare.test` | admin | gère les utilisateurs et les communautés, voit tout |
| `alice@solarshare.test` | membre | productrice (Toit Alice), compteur lié |
| `bob@solarshare.test` | membre | gros consommateur, deux pics anormaux, bon payeur |
| `carol@solarshare.test` | membre | consommatrice sobre, une chute anormale |
| `farah@solarshare.test` | membre | paie souvent en retard (risque d'impayé élevé) |
| `driss@`, `emna@solarshare.test`, `test@example.com` | membre | autres producteurs |

## Tests et qualité

```bash
php artisan test            # 116 tests (SQLite en mémoire : la base MySQL n'est jamais touchée)
vendor/bin/pint --test      # style
vendor/bin/phpstan analyse  # analyse statique (Larastan niveau 5)
php artisan ia:entrainer    # réentraîner les modèles d'IA (planifié chaque nuit)
```

La CI GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) lance tout cela à chaque push,
teste sur SQLite **et** MySQL, construit l'image Docker et vérifie qu'elle démarre.

## Architecture

```
app/
  Ai/                 algorithmes d'IA en PHP (régressions, k-means, Holt, anomalies)
  Services/           règles métier partagées par le site et l'API
  Services/Ia/        fonctionnalités d'IA des trois membres
  Policies/           autorisations (une policy par entité)
  Http/Controllers/   site (Blade) ; Api/ pour l'API JSON
  Actions/Fortify/    inscription, profil, mots de passe
resources/views/      pages Blade + Bootstrap
tests/Feature, Unit   tests
docker/, Dockerfile, docker-compose.yml, Jenkinsfile, .github/workflows   DevOps
```

Modèle de données : `User` · `Communaute` · `Installation` → `Production`, `Offre` → `Echange` → `Facture` ·
`Compteur` → `Consommation`.
