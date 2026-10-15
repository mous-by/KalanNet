# Accès d'assistance (SupAdmin, avec l'accord de l'école)

Pour résoudre le problème d'un client sans lui demander son mot de passe, un
SupAdmin peut utiliser KalanNet avec le compte de ce client, **après l'accord
d'un Admin de l'école**.

## Déroulement

1. **SupAdmin** — Configuration → Utilisateurs, filtrer l'école, icône rouge
   « Accès d'assistance » sur la ligne du compte, saisir le **motif** (10
   caractères au moins), « Envoyer la demande ».
2. Le SupAdmin arrive sur une **page d'attente** avec un compte à rebours de
   **2 minutes**.
3. **Admins de l'école** — sur toute page de KalanNet (web), une fenêtre
   apparaît dans les 10 secondes : compte concerné, motif, compte à rebours,
   boutons **Accepter** / **Refuser**. Une notification est aussi enregistrée.
4. **Accord** : la page d'attente du SupAdmin entre automatiquement dans le
   compte. **Refus ou pas de réponse en 2 minutes** : la demande expire, il
   faut la relancer.
5. Pendant l'accès, un bandeau rouge (visible du SupAdmin seul) rappelle le
   compte et l'heure de fin ; « Revenir à mon compte » ou « Déconnexion »
   ramène au compte SupAdmin.

## Règles

| Règle | Détail |
|---|---|
| Qui demande | SupAdmin uniquement |
| Qui accepte | Un Admin actif de l'école du compte (y compris l'Admin lui-même si c'est son compte). École sans Admin actif : demande impossible |
| Comptes ouvrables | Admin, Gestionnaire, enseignant, parent, revendeur actifs ; jamais un SupAdmin, un DAE ou un DCAP |
| Délais | Demande : 2 minutes. Entrée dans le compte : dans les 5 minutes après l'accord, une seule fois. Accès : 60 minutes au plus |
| Interdit pendant l'accès | Mot de passe (du client ou d'un autre compte), permissions, suppression de comptes, nouvelle demande, réponse à une demande |
| Trace côté client | Dates de dernière connexion et d'activité inchangées ; les actions faites pendant l'accès sont enregistrées au nom du compte |
| Journal | `acces_assistance` : support, compte, école, motif, statut, Admin qui a décidé et quand, adresse IP, début, fin. Consultable par les SupAdmin seuls (Configuration → Utilisateurs → Journal des accès d'assistance) |

Limite : la fenêtre d'accord s'affiche sur le site web ; un Admin qui n'utilise
que l'application mobile reçoit la notification mais doit ouvrir le site pour
répondre.

Tests : `tests/Integration/AccesAssistanceTest.php` (8 tests).
