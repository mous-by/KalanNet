# Accès d'assistance (SupAdmin)

Pour résoudre le problème d'un client sans lui demander son mot de passe, un
SupAdmin peut utiliser KalanNet avec le compte de ce client.

## Utilisation

1. **Configuration → Utilisateurs**, filtrer l'école, puis l'icône rouge
   « Accès d'assistance » sur la ligne du compte.
2. Saisir le **motif** (10 caractères au moins), puis « Ouvrir le compte ».
3. KalanNet s'affiche comme pour le client. Un bandeau rouge, visible de vous
   seul, rappelle le compte utilisé et l'heure de fin.
4. « Revenir à mon compte » (ou « Déconnexion ») ramène à votre compte SupAdmin.

## Règles

| Règle | Détail |
|---|---|
| Qui | SupAdmin uniquement |
| Comptes ouvrables | Admin, Gestionnaire, enseignant, parent, revendeur actifs ; jamais un SupAdmin, un DAE ou un DCAP |
| Durée | 60 minutes au plus, puis retour automatique à votre compte |
| Interdit pendant l'accès | Changer le mot de passe du client ou d'un autre compte, gérer les permissions, supprimer des comptes, ouvrir un second accès |
| Discrétion | L'école n'est pas prévenue ; la date de dernière connexion et d'activité du client ne change pas |
| Journal | Chaque accès (support, compte, école, motif, adresse IP, début, fin) est enregistré dans `acces_assistance`, consultable par les SupAdmin seuls : **Configuration → Utilisateurs → Journal des accès d'assistance** |

Les actions faites pendant l'accès sont enregistrées au nom du compte du
client, comme s'il les avait faites lui-même.

Conseil : mentionner dans les conditions d'utilisation que le support KalanNet
peut accéder aux comptes pour résoudre les problèmes signalés.

Tests : `tests/Integration/AccesAssistanceTest.php`.
