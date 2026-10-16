# Données de charge (base locale)

Pour tester KalanNet avec un volume réaliste, la commande
`kalannet:donnees-charge` remplit la base **locale** de données fictives.
Elle refuse de tourner en production ou sur une base qui n'est pas locale.

```bash
php artisan kalannet:donnees-charge                       # 110 écoles, 25 élèves par classe (≈ 1,5 million de lignes)
php artisan kalannet:donnees-charge --ecoles=1200 --eleves=100   # ≈ 1,1 million d'élèves, année complète de notes (environ 1 heure)
php artisan kalannet:donnees-charge --reprendre --ecoles=1200 --eleves=100   # reprend une génération interrompue
php artisan kalannet:donnees-charge --supprimer           # retire tout ce que la commande a créé
```

## Contenu

- **Pays** : environ 73 % au Mali, 23 % dans d'autres pays d'Afrique (Sénégal,
  Côte d'Ivoire, Burkina Faso, Guinée, Niger, Cameroun), 4 % en Europe et en
  Amérique (France, Belgique, Canada, États-Unis). Noms d'écoles inventés
  (« École Fondamentale Benkadi de Lafiabougou », « Lycée Le Savoir de Kayes »…),
  villes et quartiers, prénoms et noms de la région, téléphones au format du
  pays, montants dans la monnaie du pays.
- **Types** au Mali : Fondamental I, Fondamental II, Collège, Secondaire
  général, technique, Complexes scolaires (tous les ordres), Écoles de santé ;
  hors Mali : Primaire, Secondaire général, Complexe scolaire. Une école
  malienne sur quatre (hors technique et santé) est franco-arabe ; les
  complexes et une école sur trois sont privés.
- **Ordres** : Jardin (Petite et Grande section, avec leur maître), Fondamental I
  (1ère à 6ème), Fondamental II (7ème à 9ème), Secondaire général (10ème commune,
  11ème Sciences / SES, 12ème TSE / TSS), Technique (Génie civil), Santé
  (filière Infirmier d'État).
- **Année 2025-2026 complète** : Fondamental I en compositions mensuelles
  (octobre à mai, sur 10), autres ordres avec un devoir et une composition par
  trimestre (sur 20). Les notes sont toujours les mêmes d'une génération à
  l'autre ; moyenne d'environ 12/20.
- Enseignants, emplois du temps, présences, parents (deux élèves sur trois),
  inscriptions, absences et retards (environ 15 % des élèves), paiements en deux
  tranches dans les écoles privées (avec caisse et encaissements), 2 % d'abandons.

## Connexion

`admin1@charge.kalannet.test` … `adminN@charge.kalannet.test` (N = nombre d'écoles), mot de passe
`KalanNet2026` (modifiable par `--mot-de-passe=`). Les adresses
`@charge.kalannet.test` n'existent pas.

## Suppression

Les écoles créées sont listées dans `storage/app/donnees-charge.json`. La
suppression ne touche que ces écoles (et vérifie leur adresse
`@charge.kalannet.test`) : vos autres données ne sont pas modifiées. Vérifié :
après génération puis suppression, chaque table retrouve exactement son
nombre de lignes d'origine.

## Après un `git clone`

Les données ne sont pas dans git (plusieurs Go) : `php artisan migrate` crée
seulement les tables. Deux façons d'obtenir la même base :

1. **La régénérer** (même noms et mêmes notes à chaque fois) :
   `php artisan migrate` puis
   `php artisan kalannet:donnees-charge --ecoles=1200 --eleves=100`.
2. **La copier** depuis une machine qui l'a déjà :
   `mysqldump --single-transaction --quick db_kalannet | gzip > kalannet.sql.gz`,
   puis sur la nouvelle machine
   `gunzip < kalannet.sql.gz | mysql db_kalannet`.
