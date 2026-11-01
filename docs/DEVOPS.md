# DevOps

## Vue d'ensemble

```
 push / pull request
        │
        ▼
 GitHub Actions ──► qualité (Pint, Larastan, composer audit)
                ──► tests (SQLite + MySQL, couverture, rapport JUnit)
                ──► SonarCloud (si activé)
                ──► image Docker ──► GitHub Container Registry (main, develop, tags)
                ──► test de démarrage (docker compose + curl)

 Exécution : docker compose ─► app (Apache + PHP 8.3) · queue · scheduler · MySQL 8.4 · Mailpit
                            └► (profil monitoring) Prometheus ◄─ /metrics ─ app ; Grafana
```

## Conteneurs

| Fichier | Rôle |
|---|---|
| `Dockerfile` | Image multi-étapes : dépendances Composer sans outils de dev, puis PHP 8.3 + Apache avec les extensions nécessaires, OPcache, en-têtes de sécurité, healthcheck sur `/up` |
| `docker/entrypoint.sh` | Attend la base, lance les migrations, met en cache la configuration ; selon `CONTAINER_ROLE` : site, file d'attente ou planificateur |
| `docker-compose.yml` | `app` (port 8080), `queue` (envoi des e-mails et notifications), `scheduler` (expiration des offres, entraînement IA chaque nuit), `db` (MySQL 8.4 avec healthcheck), `mailpit` (boîte de réception de test, port 8025) |

```bash
php artisan key:generate --show        # mettre la valeur dans APP_KEY de .env
docker compose up -d --build
docker compose ps                      # tous les services "healthy"
docker compose logs -f app
docker compose exec app php artisan ia:entrainer
docker compose down                    # -v pour effacer aussi les données
```

## Intégration et livraison continues (`.github/workflows/ci.yml`)

| Job | Contenu |
|---|---|
| `qualite` | `pint --test`, `phpstan analyse` (niveau 5, avec baseline), `composer audit` |
| `tests` | Matrice SQLite / MySQL 8.4 ; couverture (pcov) et rapport JUnit publiés en artefacts |
| `sonar` | Analyse SonarCloud, activée en créant la variable `SONAR_ENABLED=true` et le secret `SONAR_TOKEN` |
| `docker` | Construction de l'image avec cache ; publication sur `ghcr.io/<propriétaire>/solarshare` pour main, develop et les tags `v*` |
| `smoke` | Démarre l'image avec docker compose et vérifie `/up`, `/login` et `/metrics` |

`Jenkinsfile` décrit le même pipeline pour un serveur Jenkins.

## Supervision

- `GET /up` : santé de l'application (utilisée par le healthcheck Docker).
- `GET /metrics` : métriques au format Prometheus, protégées par `Authorization: Bearer <METRICS_TOKEN>` —
  disponibilité et latence de la base, utilisateurs, offres et échanges par statut, montant des factures,
  factures en retard, compteurs en panne, file d'attente, qualité (AUC) du modèle d'IA.
- `docker compose --profile monitoring up -d` : Prometheus (http://localhost:9090) collecte `/metrics` toutes les
  30 s ; Grafana (http://localhost:3000, admin / admin) affiche le tableau de bord **SolarShare** déjà configuré.

Le jeton `changez-moi` de la démonstration doit être changé (`METRICS_TOKEN` et `docker/prometheus/prometheus.yml`).
