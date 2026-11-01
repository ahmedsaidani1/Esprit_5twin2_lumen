# Fonctionnalités d'intelligence artificielle

Aucune API externe : tout est calculé dans l'application, à partir des données de la plateforme.
Les algorithmes sont écrits en PHP dans `app/Ai` (chacun vérifié par un test sur des données dont on connaît
la réponse), et utilisés par les services de `app/Services/Ia`. Les paramètres sont dans `config/ia.php`.

## Membre A — échanges et factures

### Risque d'impayé (`RisqueImpayeService`, `RegressionLogistique`)

- **Question** : cette facture risque-t-elle de ne pas être payée à l'échéance ?
- **Données d'apprentissage** : les factures dont l'issue est connue — payée (0) ou impayée après l'échéance (1).
- **Variables**, calculées avec ce qui était connu à la date d'émission (pas d'information du futur) :
  montant, taux d'impayés passés de l'acheteur (lissage de Laplace), nombre d'échanges passés,
  délai de paiement accordé, acheteur extérieur à la communauté de l'échange.
- **Modèle** : régression logistique, variables standardisées, descente de gradient avec régularisation L2,
  classes équilibrées (les impayés sont minoritaires).
- **Évaluation** : 1 facture sur 4 est mise de côté pour le test → exactitude, précision, rappel, AUC
  (sur les données de démonstration : environ 82 % d'exactitude, AUC 0,78).
- **Explication** : chaque prédiction affiche les 3 variables qui ont le plus pesé (poids × valeur standardisée).
- **Confidentialité** : le score n'est montré qu'au vendeur et à l'administrateur, jamais à l'acheteur.
- **Mise à jour** : `php artisan ia:entrainer` (planifié chaque nuit) ou bouton de l'admin dans le Laboratoire IA.

### Recommandation d'offres (`RecommandationOffresService`)

Filtrage par le contenu : chaque offre disponible reçoit un score qui combine son prix, sa proximité avec les
prix et quantités habituels de l'acheteur, l'appartenance à sa communauté et la fiabilité du vendeur
(taux d'acceptation lissé). Sans historique (démarrage à froid), le prix et la proximité comptent davantage.
Chaque recommandation affiche ses raisons.

## Membre B — production et offres

### Prévision de production (`PrevisionProductionService`, `LissageHolt`)

- Série quotidienne des 60 derniers jours ; les jours manquants sont estimés par interpolation linéaire.
- Lissage exponentiel double (Holt) : un niveau et une tendance mis à jour chaque jour.
- **Apprentissage** : α et β sont choisis par recherche sur grille en minimisant l'erreur des prévisions à un jour.
- **Sortie** : 7 jours de prévision avec un intervalle à 95 % qui s'élargit avec l'horizon ; erreurs MAE et MAPE affichées.
- **Limite assumée** : le modèle ne connaît pas la météo, d'où une erreur importante les jours nuageux.

### Offre suggérée

Quantité = production prévue − autoconsommation (mesurée par le **compteur lié** du membre C, sinon 30 %),
bornée par le stock encore proposable. Prix = médiane des échanges récents, relevé ou baissé selon le rapport
entre la demande et l'offre du marché (± 20 % au plus). Le bouton « Créer cette offre » pré-remplit le formulaire.

## Membre C — compteurs et consommations

### Détection d'anomalies (`DetecteurAnomalies`)

Score z **robuste** : `z = 0,6745 × (x − médiane) / MAD`, insensible aux valeurs extrêmes, calculé après avoir
retiré l'effet du jour de la semaine (un dimanche est comparé aux autres dimanches). |z| > 3,5 = anomalie
(pic ou chute). Sur les données de démonstration, les 4 anomalies injectées sont retrouvées, sans fausse alerte.

### Prévision de consommation (`RegressionLineaire`)

Régression linéaire multiple résolue par les équations normales (élimination de Gauss, légère régularisation
ridge) : une tendance + un effet par jour de la semaine. Prévision sur 30 jours, R² et intervalle à 95 % affichés.

### Profils et conseils (`KMeans`)

Chaque consommateur ayant au moins 14 relevés est décrit par 3 mesures standardisées (moyenne par jour,
variabilité, part du week-end), regroupées par **k-means** (k = 3, initialisation k-means++, 10 essais, graine fixe
pour un résultat reproductible). Les groupes sont nommés Sobre / Modéré / Intensif. Des règles transforment le
profil, les anomalies et la tendance en conseils, et l'économie mensuelle est estimée (tarif du réseau contre
prix moyen du marché local).
