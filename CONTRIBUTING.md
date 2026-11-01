# Contribuer à SolarShare

## Organisation de l'équipe

| Membre | Périmètre | Fonctionnalités avancées | IA |
|---|---|---|---|
| **A** | Échanges, factures | Notifications, facture PDF, envoi par e-mail | Risque d'impayé (régression logistique), recommandation d'offres |
| **B** | Installations, productions, offres | Graphique de production, import / export CSV | Prévision de production (Holt), offre suggérée |
| **C** | Compteurs, consommations | Graphique de consommation, import / export CSV | Détection d'anomalies, prévision, profils k-means et conseils |
| Tous | Gestion des utilisateurs, DevOps, cohérence | | |

Le fichier [`.github/CODEOWNERS`](.github/CODEOWNERS) désigne le relecteur de chaque partie du code.

## Branches (Git Flow simplifié)

```
main        version livrée, protégée, mise à jour uniquement par pull request depuis develop
develop     intégration continue de l'équipe
feature/<membre>-<sujet>   une fonctionnalité, ex. feature/c-detection-anomalies
fix/<sujet>                une correction
```

1. Partir de `develop` à jour : `git checkout develop && git pull`
2. Créer sa branche : `git checkout -b feature/b-import-csv`
3. Petits commits clairs (voir ci-dessous), puis `git push -u origin feature/b-import-csv`
4. Ouvrir une **pull request vers `develop`** avec le modèle fourni ; la CI doit être verte
5. Au moins **une relecture** d'un autre membre avant la fusion (squash ou merge)
6. Pour une livraison : pull request `develop` → `main`, puis un tag `vX.Y.Z`

## Messages de commit (Conventional Commits)

```
<type>(<portée>): <résumé à l'impératif>

feat(factures): envoyer la facture PDF par e-mail à l'acheteur
fix(consommations): refuser deux relevés le même jour sur MySQL
test(ia): couvrir la détection d'anomalies
docs: expliquer le lancement avec Docker
```

Types : `feat`, `fix`, `test`, `refactor`, `docs`, `ci`, `chore`, `style`.

## Avant de pousser

```bash
vendor/bin/pint            # met le code au bon format
vendor/bin/phpstan analyse # analyse statique (niveau 5)
php artisan test           # tous les tests doivent passer
```

La CI (GitHub Actions) relance exactement ces vérifications à chaque push et bloque une pull request en échec.

## Règles de code

- Validation des formulaires en HTML (`required`, `min`, `maxlength`…) **et** côté serveur ; erreurs affichées sous le champ avec `@error`.
- Règles métier dans `app/Services`, partagées par le site et l'API ; autorisations dans `app/Policies`.
- Toute nouvelle fonctionnalité arrive avec ses tests (`tests/Feature`, `tests/Unit`).
- Textes de l'interface en français.
