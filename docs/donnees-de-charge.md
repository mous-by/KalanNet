# Données de charge (base locale)

Pour tester KalanNet avec un volume réaliste, la commande
`kalannet:donnees-charge` remplit la base **locale** de données fictives.
Elle refuse de tourner en production ou sur une base qui n'est pas locale.

```bash
php artisan kalannet:donnees-charge                       # 110 écoles, 25 élèves par classe (≈ 1,5 million de lignes)
php artisan kalannet:donnees-charge --ecoles=150 --eleves=30
php artisan kalannet:donnees-charge --supprimer           # retire tout ce que la commande a créé
```

## Contenu

- **Écoles** (pour 110) : 22 Fondamental I, 14 Fondamental II, 22 Collèges,
  16 Secondaire général, 8 Secondaire technique, 22 Complexes scolaires (tous les
  ordres), 6 Écoles de santé. Une sur quatre (hors technique et santé) est
  franco-arabe ; les complexes et une école sur trois sont privés.
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

`admin1@charge.kalannet.test` … `admin110@charge.kalannet.test`, mot de passe
`KalanNet2026` (modifiable par `--mot-de-passe=`). Les adresses
`@charge.kalannet.test` n'existent pas.

## Suppression

Les écoles créées sont listées dans `storage/app/donnees-charge.json`. La
suppression ne touche que ces écoles (et vérifie leur adresse
`@charge.kalannet.test`) : vos autres données ne sont pas modifiées. Vérifié :
après génération puis suppression, chaque table retrouve exactement son
nombre de lignes d'origine.
