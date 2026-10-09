# MANUEL UTILISATEUR — TK MOTORS V1

ETS TK MOTORS  
Slogan : « Votre Moto, Notre Passion ! »

Ce manuel explique comment utiliser l’application TK MOTORS dans sa version 1. Il s’adresse au patron principal, au patron secondaire et aux employés. Il ne demande aucune connaissance en programmation.

Les noms de pages, de menus et de boutons sont ceux qui apparaissent à l’écran. Les montants s’affichent tels qu’ils sont enregistrés, sans symbole de monnaie.

Aucune capture d’écran n’est jointe : les images disponibles ne correspondent pas à cette version (le menu Locations et l’ancien lien d’invitation n’y figurent plus). Suivez les noms indiqués dans ce document.

---

## Sommaire

- [Partie A — Présentation générale](#partie-a--présentation-générale)
- [Partie B — Manuel du patron](#partie-b--manuel-du-patron)
- [Partie C — Manuel de l’employé](#partie-c--manuel-de-lemployé)
- [Partie D — Procédures pas à pas](#partie-d--procédures-pas-à-pas)
- [Partie E — Questions fréquentes et dépannage](#partie-e--questions-fréquentes-et-dépannage)
- [Partie F — Glossaire](#partie-f--glossaire)

---

## Partie A — Présentation générale

### À quoi sert TK MOTORS

TK MOTORS sert à gérer le quotidien d’ETS TK MOTORS :

- le catalogue des articles et leurs prix ;
- le stock de la Boutique et du Dépôt ;
- les arrivages de marchandise ;
- les ventes et leurs annulations ;
- la caisse et les dépenses ;
- les demandes de produits faites par les clients ;
- les alertes ;
- une suggestion d’épargne ;
- le journal des actions importantes.

L’application ouvre d’abord un écran avec le logo, pendant quelques secondes, puis envoie vers la connexion ou vers le tableau de bord si la personne est déjà connectée.

### Les trois rôles

| Rôle à l’écran | Qui | Ce qu’il peut faire |
| --- | --- | --- |
| Patron principal | Le premier compte de l’organisation, puis la personne qui dirige | Tout ce que peut un patron, et inviter un patron secondaire |
| Patron secondaire | Un second patron, invité par le patron principal | Le même travail de gestion qu’un patron, sauf inviter un autre patron |
| Employé | Une personne invitée pour le travail quotidien | Consulter le catalogue, vendre, enregistrer un arrivage, enregistrer et traiter une demande |

Il n’existe pas, dans cette version, d’écran pour désactiver le compte d’une autre personne. Le patron peut seulement révoquer une invitation qui n’a pas encore été utilisée. Chaque personne peut supprimer son propre compte depuis son profil.

### Boutique et Dépôt

| Emplacement | Rôle |
| --- | --- |
| Boutique | Stock que l’on peut vendre |
| Dépôt | Stock entreposé. Il n’est pas vendable tant qu’il n’a pas été transféré vers la Boutique |

Une vente retire toujours la quantité de la Boutique. Si la pièce est seulement au Dépôt, la vente est refusée jusqu’à un transfert.

### Règles principales du stock

- Un article neuf commence à 0 en Boutique et à 0 au Dépôt.
- Un arrivage n’augmente le stock qu’après validation par un patron.
- Le patron peut aussi, sur la fiche de l’article, enregistrer une entrée directe, un transfert Dépôt vers Boutique, ou un ajustement exceptionnel.
- Un ajustement exceptionnel corrige une quantité. Il n’annule pas une vente et ne touche pas la caisse.
- On ne peut pas descendre une quantité en dessous de zéro.
- Un article désactivé ne peut plus être vendu, approvisionné ou ajusté. Son historique reste.
- Le stock faible concerne la Boutique : un article actif dont la quantité est au moins 1 et au plus égale au seuil. Le seuil par défaut est 5. Une quantité à 0 est un article épuisé, ce n’est pas une alerte de stock faible.
- Ce seuil n’est pas modifiable article par article dans l’écran. Il n’y a pas de réglage « seuil » dans le formulaire d’article.

### Règles principales des ventes

- Une vente porte sur un seul article à la fois.
- Le prix appliqué est le prix de vente du catalogue au moment de la vente. Il est conservé sur la vente, même si le catalogue change ensuite.
- Le prix d’achat du catalogue est aussi conservé sur la vente, pour le calcul du résultat. S’il n’est pas renseigné, la vente est refusée.
- Le total est la quantité multipliée par le prix de vente unitaire.
- La vente retire le stock Boutique et ajoute le total à la caisse, dans la même opération.
- Seul un patron peut annuler une vente déjà conclue. L’annulation exige un motif. Elle remet le stock en Boutique et retire le même montant de la caisse.
- Une vente déjà annulée ne peut pas l’être une seconde fois.

### Règles principales de la caisse

- Le solde ne devient jamais négatif.
- Une vente conclue crée une entrée de caisse.
- L’annulation d’une vente crée une sortie du même montant.
- Enregistrer une dépense ne retire rien. Le retrait a lieu seulement quand un patron valide la dépense, et seulement si le solde suffit.
- Si le solde ne suffit pas, la dépense est refusée. La caisse n’est pas débitée. La dépense reste visible avec le motif de refus.
- L’épargne suggérée n’enlève aucun argent.

### Limites de la version 1

- La gestion des locations n’est pas disponible. Le menu Locations n’apparaît pas.
- L’envoi réel des e-mails n’est pas activé. Une invitation se transmet par un code, par le moyen choisi par le patron.
- Le seuil de stock faible est de 5 pour les articles. Il n’y a pas d’écran pour le changer.
- Il n’y a pas de panier : une vente égale un article.
- Il n’y a pas de lecteur de code-barres intégré. On peut toutefois saisir ou coller un code-barres dans les champs de recherche.
- Il n’y a pas de page séparée nommée « Rapports ». Les chiffres, classements et le graphique sont sur le tableau de bord, réservés aux patrons.
- Le patron ne peut pas changer le mot de passe d’une autre personne depuis Utilisateurs.
- Le lien « Mot de passe oublié ? » existe, mais l’e-mail de réinitialisation n’est pas envoyé dans cette version.
- Les messages d’échec de connexion s’affichent en anglais, car l’application n’a pas de traduction française de ces messages précis.

### Avant de se connecter

1. Disposer de l’adresse de l’application fournie par l’organisation.
2. Avoir un compte :
   - le tout premier accès, s’il est encore ouvert, passe par « Créer le compte patron » sur la page Connexion ;
   - ensuite, les autres personnes reçoivent un code d’invitation.
3. Connaître son adresse e-mail et son mot de passe. Le mot de passe compte au moins 8 caractères. Le code d’invitation n’est pas un mot de passe.

### Tableau des droits réels

Une coche signifie que l’action est autorisée. Une case vide signifie qu’elle est refusée.

| Action | Patron principal | Patron secondaire | Employé |
| --- | --- | --- | --- |
| Se connecter, voir son profil, changer sa photo et son mot de passe | Oui | Oui | Oui |
| Tableau de bord : accueil, stock, arrivages | Oui | Oui | Oui |
| Tableau de bord : ventes, résultat, caisse, dépenses, classements, graphique | Oui | Oui |  |
| Consulter le catalogue, les prix de vente, la Boutique et le Dépôt | Oui | Oui | Oui |
| Voir le prix d’achat | Oui | Oui |  |
| Créer, modifier, activer ou désactiver un article | Oui | Oui |  |
| Entrée directe, transfert, ajustement exceptionnel | Oui | Oui |  |
| Enregistrer un arrivage | Oui | Oui | Oui |
| Voir tous les arrivages de l’organisation | Oui | Oui |  |
| Voir seulement les arrivages qu’il a lui-même enregistrés |  |  | Oui |
| Valider ou rejeter un arrivage | Oui | Oui |  |
| Enregistrer une vente et consulter l’historique des ventes | Oui | Oui | Oui |
| Voir le coût et le résultat d’une vente | Oui | Oui |  |
| Annuler une vente | Oui | Oui |  |
| Caisse et dépenses | Oui | Oui |  |
| Épargne suggérée | Oui | Oui |  |
| Journal d’audit | Oui | Oui |  |
| Enregistrer, satisfaire ou annuler une demande de produit | Oui | Oui | Oui |
| Alertes (sans les montants de caisse ni les échéances de location) | Oui | Oui | Oui |
| Inviter un employé | Oui | Oui |  |
| Inviter un patron secondaire | Oui |  |  |
| Inviter un patron principal |  |  |  |
| Gérer les locations | Non disponible dans la V1 | Non disponible dans la V1 | Non disponible dans la V1 |

---

## Partie B — Manuel du patron

Le patron principal et le patron secondaire voient les mêmes menus, avec une seule différence : seul le patron principal peut choisir le rôle « Patron secondaire » en invitant quelqu’un.

### A. Connexion et tableau de bord

#### Connexion

1. Ouvrir l’adresse de l’application. Le logo s’affiche, puis la page **Connexion**.
2. Renseigner **Adresse e-mail** et **Mot de passe**.
3. L’œil à côté du mot de passe permet de l’afficher ou de le masquer.
4. Choisir **Se connecter**.

Si les identifiants sont faux, le message affiché est : « These credentials do not match our records. » Cela veut dire que l’adresse ou le mot de passe ne correspond pas. Après plusieurs essais rapides, la connexion est bloquée un moment et le message commence par « Too many login attempts ». Attendre, puis réessayer.

Le lien **Mot de passe oublié ?** ouvre une page, mais cette version n’envoie pas l’e-mail. Un mot de passe perdu demande une intervention technique. Le patron ne peut pas le remplacer depuis Utilisateurs.

Le lien **Activer mon compte** sert à une personne qui a reçu un code d’invitation, pas à une personne qui a déjà un mot de passe.

#### Navigation

La barre du haut, sur fond bleu marine, contient :

- **Tableau de bord**
- **Catalogue**
- **Ventes**
- **Stock** : Vue générale, Boutique, Dépôt, Mouvements
- **Finances** : Caisse, Dépenses
- **Demandes**
- **Alertes**
- **Épargne**
- **Audit**
- **Utilisateurs**
- **Approvisionnements** : Nouvel arrivage, En attente, Historique, Tous les arrivages

Le menu **Locations** n’est pas affiché.

À droite, le nom de la personne connectée ouvre **Utilisateurs**, **Profil** et **Déconnexion**. Sur un petit écran, les mêmes liens sont dans le menu qui s’ouvre avec l’icône de trois traits.

#### Identité

Le tableau de bord commence par une photo, ou les initiales s’il n’y a pas de photo, puis une phrase du type « Bienvenue dans TK MOTORS », suivie de la civilité seulement si elle a été renseignée, puis du nom. Le slogan est rappelé sous le titre.

#### Lire les indicateurs

La zone **Pilotage** est réservée aux patrons. Les cartes sont :

- **Ventes du jour** : montant, nombre d’articles et nombre de ventes conclues aujourd’hui
- **Ventes de la période**
- **Résultat de la période** : total des ventes conclues moins leur coût d’achat, sur la période choisie
- **Montant en caisse**
- **Dépenses validées**
- **Stock faible**

En dessous : **Articles les plus vendus**, **Articles les moins vendus**, puis **Graphique des ventes**.

Les boutons de période sont **Jour**, **Semaine**, **Mois** et **Année**. Jour détaille les heures. Semaine et Mois détaillent les jours. Année détaille les mois. Seules les ventes au statut **Conclue** entrent dans ces chiffres. Une vente **Annulée** n’y entre pas.

La zone **Stock** montre **Articles actifs**, **Stock boutique** (« Disponible à la vente ») et **Stock dépôt** (« Non disponible à la vente »), plus les liens **Voir la Boutique**, **Voir le Dépôt** et **Voir les mouvements**. S’il existe des articles à zéro en Boutique, une carte **Articles épuisés en boutique** les liste.

La zone **Approvisionnements** montre **Arrivages en attente**, **Nouvel arrivage**, **Derniers arrivages validés** et les arrivages rejetés récents. S’il n’y en a pas, le texte est **Aucune donnée**.

#### Zéro et absence de données

- Un montant **0.00** signifie que le calcul a été fait et que le total est nul. Ce n’est pas une panne.
- **Aucune vente enregistrée.** signifie qu’aucune vente conclue n’alimente encore le classement.
- **Aucune donnée** sur les arrivages signifie qu’il n’y a pas encore de ligne validée ou rejetée à montrer.
- **Aucun article actif en boutique n’est au seuil de stock ou en dessous.** signifie que l’alerte de stock faible a été calculée et qu’elle est vide.
- Le graphique vide signifie qu’il n’y a pas de vente conclue sur la période, pas que le graphique est cassé.

### B. Gestion des utilisateurs

Menu **Utilisateurs**, ou le même lien dans le menu du nom.

#### Créer une invitation

1. Choisir **Inviter un utilisateur**.
2. Renseigner **Prénom**, **Nom**, **E-mail**, **Civilité** (Monsieur ou Madame) et **Rôle**.
3. Le patron principal peut choisir **Employé** ou **Patron secondaire**.
4. Le patron secondaire ne voit que **Employé**.
5. Choisir **Créer l’invitation**.

L’adresse e-mail sert à identifier la personne. Elle est obligatoire. Aucun e-mail n’est envoyé. Le texte de confirmation le dit explicitement et ne prétend pas que le message a été envoyé.

#### Le code à 5 chiffres

Après la création, la page **Utilisateurs** affiche une seule fois le bloc **Code d’activation à transmettre**, avec cinq chiffres, de 00000 à 99999. Les zéros au début comptent : 01234 n’est pas 1234.

Ce code :

- est créé par le serveur ;
- n’est pas un mot de passe ;
- expire au bout de **7 jours** ;
- ne sert qu’**une fois** ;
- n’est plus affiché si l’on quitte ou recharge la page ;
- n’apparaît pas dans le tableau des invitations ni dans le journal d’audit.

Le tableau liste la personne, l’e-mail, la civilité, le rôle, le statut et la personne qui a invité. Une invitation en attente indique « jusqu’au » suivi de la date et de l’heure. Le bouton **Révoquer** retire une invitation encore utilisable.

#### Communiquer le code

Le patron remet les cinq chiffres à la personne invitée, par le moyen de son choix : de vive voix, par téléphone, par un message qu’il contrôle. Il faut aussi lui indiquer l’adresse de l’application et l’adresse e-mail qui a été saisie, car le compte sera créé avec cette adresse.

Ne pas laisser le code affiché sur un écran visible par d’autres personnes. S’il a été vu par erreur, choisir **Révoquer**, puis créer une nouvelle invitation.

#### Activation

La personne invitée ouvre **Activer mon compte** sur la page Connexion. Elle saisit le code, choisit son mot de passe, le confirme, puis choisit **Activer le compte**. Elle arrive sur le tableau de bord. Le message est « Votre compte est activé. »

#### Rôles que l’on peut donner

- Employé : patron principal et patron secondaire.
- Patron secondaire : patron principal seulement.
- Patron principal : personne ne peut l’inviter. Le message est « Le patron principal ne peut pas être invité. »

#### Désactivation et suppression

- Invitation non utilisée : **Révoquer**. Le statut devient révoqué. Le code ne marche plus.
- Compte déjà activé : il n’y a pas de bouton pour le désactiver ou le supprimer à la place de la personne. La personne peut supprimer son propre compte dans **Profil**, section **Supprimer le compte**. Cette suppression est définitive et demande son mot de passe. Le champ de confirmation porte l’indication « Mot de passe ».

#### Erreurs fréquentes

| Situation | Message ou effet |
| --- | --- |
| Adresse déjà utilisée par un compte | « Cette adresse e-mail est déjà utilisée. » |
| Invitation encore en attente pour la même adresse | « Une invitation est déjà en attente pour cette adresse. » Révoquer l’ancienne, ou attendre qu’elle expire, avant d’en créer une nouvelle |
| Patron secondaire qui choisit un patron | « Vous n’êtes pas autorisé à inviter un patron. » |
| Code faux, expiré, révoqué ou déjà utilisé | « Ce code d’invitation n’est pas valable. » Le message est le même dans tous ces cas |
| Trop d’essais de code | Après 5 essais en 15 minutes, la page d’activation se bloque temporairement |
| Code oublié après avoir quitté la page | Il n’est plus affiché. Révoquer l’invitation si elle est encore en attente, puis en créer une nouvelle |

Les invitations créées avant cette version, avec un long lien, ne peuvent plus être activées. Il faut les révoquer et en créer une nouvelle pour obtenir un code.

### C. Gestion des articles

Menu **Catalogue**. La page s’intitule **Articles**.

#### Création

**Nouvel article**, puis **Créer l’article**.

Champs :

- **Code article** : obligatoire, unique dans l’organisation, mis en majuscules
- **Code-barres / QR (optionnel)** : unique s’il est rempli. Plusieurs articles peuvent rester sans code-barres
- **Nom**
- **Catégorie** : on peut reprendre une catégorie déjà utilisée ou en écrire une nouvelle
- **Description** : facultative
- **Prix d'achat** : obligatoire à la création, visible seulement par un patron
- **Prix de vente** : obligatoire

Le prix d’achat est une donnée interne. Il sert au résultat. L’employé ne le voit pas.

#### Modification

Ouvrir l’article, choisir **Modifier**, puis **Enregistrer**. Les mêmes champs sont proposés. Le code doit rester unique.

#### Activation

Sur la fiche, **Désactiver** ou **Réactiver**. Un article désactivé reste dans l’historique. Il n’est plus proposé à la vente ni à l’arrivage, et les formulaires de stock de sa fiche disparaissent.

#### Recherche

Sur **Articles**, le champ **Nom, code ou code-barres**, la liste **Toutes les catégories**, la liste **Tous les états** (Actifs, Désactivés), puis **Filtrer**.

Le seuil de stock faible n’est pas un champ de cette fiche.

### D. Stock

Menu **Stock**.

- **Vue générale** : articles, quantités, état Actif ou Désactivé, filtre Tous / Actifs / Désactivés
- **Boutique** et **Dépôt** : quantités de l’emplacement, filtre de catégorie et d’état
- **Mouvements** : historique, filtres **Tous les types** et **Tous les emplacements**

Les types de mouvement sont : Entrée de stock, Transfert sortant, Transfert entrant, Sortie vente, Restauration de vente, Ajustement.

Sur la fiche d’un article actif, le patron voit trois formulaires.

#### Entrée de stock

**Emplacement** (Boutique ou Dépôt), **Quantité**, **Note** facultative, bouton **Enregistrer l’entrée**. Le stock augmente tout de suite. Ce n’est pas un arrivage à valider.

#### Transfert

Titre : **Transfert dépôt → boutique**. **Quantité**, **Note** facultative, bouton **Transférer**. Le Dépôt diminue, la Boutique augmente. Si le Dépôt n’a pas assez, le transfert est refusé.

#### Ajustement exceptionnel

**Emplacement**, **Sens** (Augmenter ou Diminuer), **Quantité**, **Motif obligatoire**, **Précision**.

Motifs : Perte, Casse, Erreur de comptage, Différence d’inventaire, Pièce retrouvée, Erreur de saisie, Autre correction exceptionnelle.

La précision est obligatoire seulement pour « Autre correction exceptionnelle ». Le bouton est **Ajuster**.

Le texte de l’écran le dit : ce formulaire n’annule pas une vente. Il laisse une trace de type Ajustement, avec le motif, dans les mouvements et dans l’audit. Il ne crée pas d’entrée ni de sortie de caisse.

### E. Approvisionnements

Menu **Approvisionnements**.

- **Nouvel arrivage**
- **En attente** (page **Arrivages en attente**)
- **Historique** (page **Historique des arrivages**, statuts Validé et Rejeté)
- **Tous les arrivages** (page **Arrivages**)

#### Enregistrer

Champs : **Article**, **Emplacement**, **Quantité**, **Référence / fournisseur (optionnel)**. Bouton **Enregistrer l’arrivage**.

Le stock ne change pas à cette étape. Le statut est **En attente**.

#### Valider

Ouvrir l’arrivage. Choisir **Valider l’arrivage**, lire la confirmation, puis **Confirmer la validation**. Le stock de l’emplacement indiqué augmente de la quantité. L’action est définitive. Le statut devient **Validé**.

#### Rejeter

Choisir **Rejeter l’arrivage**, saisir **Motif obligatoire**, puis **Confirmer le rejet**. Le stock ne change pas. L’arrivage reste dans l’historique avec le motif. Le statut devient **Rejeté**.

Un arrivage déjà traité ne peut plus être validé ou rejeté.

### F. Ventes

Menu **Ventes**, bouton **Nouvelle vente**.

1. Saisir un nom, un code ou un code-barres, puis **Rechercher**.
2. Choisir l’article. La ligne montre le code, le prix de vente et la quantité Boutique. Le patron voit aussi le prix d’achat.
3. Saisir la **Quantité**. L’écran rappelle le stock Boutique disponible.
4. Choisir **Enregistrer la vente**.

Le prix appliqué est le prix de vente du catalogue. Le total est quantité × prix unitaire. Il n’y a pas de remise sur cet écran.

Si l’enregistrement réussit, une référence du type VTE-000001 est créée, le stock Boutique baisse, et la caisse augmente du total.

#### Historique

La page **Ventes** filtre par :

- **Référence, article, code ou code-barres**
- **Vendeur**
- **Statut** : Conclue ou Annulée
- **Du** et **Au**

Puis **Filtrer**. Ouvrir une ligne pour le détail.

#### Détail et prix figés

La fiche montre l’article, la quantité, le **Prix de vente unitaire**, le **Total**, l’emplacement, la date et l’heure, le statut et le vendeur.

Pour un patron, elle montre aussi le **Coût d’achat unitaire**, le **Coût total** et le **Résultat**. Ces trois montants sont ceux du moment de la vente. Changer plus tard le prix du catalogue ne les modifie pas.

#### Annuler

Sur une vente **Conclue**, le patron saisit **Motif d’annulation** et choisit **Annuler la vente**.

Effets :

- le statut passe à **Annulée** ;
- la quantité revient en Boutique, par un mouvement **Restauration de vente** ;
- la caisse diminue du total de la vente, si le solde le permet ;
- le motif, la date et la personne qui annule restent sur la fiche.

Si la caisse ne couvre pas le montant, l’annulation est refusée et rien n’est modifié.

### G. Caisse

Menu **Finances**, puis **Caisse**.

L’écran montre le **Solde** et l’historique :

- **Référence**, du type CAI-000001
- **Libellé** : encaissement d’une vente, annulation, ou dépense
- **Sens** : Entrée ou Sortie
- **Montant**
- **Solde après**
- **Utilisateur**
- **Date**

S’il n’y a aucun mouvement : **Aucun mouvement de caisse.**

Lien utile : **Voir les dépenses**.

Une vente conclue et son encaissement vont ensemble. Une annulation et sa sortie de caisse vont ensemble. On ne saisit pas un montant de caisse à la main.

### H. Dépenses

Menu **Finances**, puis **Dépenses**.

#### Enregistrer

**Nouvelle dépense**. Champs **Montant**, **Motif**, **Date**. Bouton **Enregistrer**.

Le texte de l’écran précise que l’enregistrement ne débite pas la caisse.

La dépense reçoit une référence du type DEP-000001 et le statut **En attente**.

#### Consulter

Filtres **Référence ou motif** et **Statut** (En attente, Validée, Refusée), puis **Filtrer**.

#### Valider

Ouvrir la dépense. Le bouton **Valider la dépense** débite la caisse si le solde couvre le montant. Le statut devient **Validée**.

#### Refuser

Champ **Refuser avec un motif**, bouton **Refuser**. Le statut devient **Refusée**. La caisse ne change pas.

#### Solde insuffisant

Si l’on choisit **Valider la dépense** alors que la caisse ne suffit pas, la dépense est refusée automatiquement. La décision affichée est : « Solde de caisse insuffisant. La dépense est refusée et la caisse n’est pas débitée. » L’historique de la dépense est conservé.

Une dépense déjà traitée ne peut plus être validée ou refusée.

### I. Rapports

Il n’y a pas de menu Rapports. Tout est sur le **Tableau de bord**, zone **Pilotage**, pour les patrons.

Filtres réellement disponibles : **Jour**, **Semaine**, **Mois**, **Année**.

On y lit :

- le chiffre des ventes du jour et de la période ;
- le résultat de la période ;
- les dépenses validées de la période ;
- les articles les plus vendus et les moins vendus ;
- le nombre d’articles en stock faible ;
- le graphique des ventes.

Les ventes annulées ne comptent pas dans ces totaux. Les dépenses seulement enregistrées, encore en attente ou refusées, ne comptent pas dans **Dépenses validées**.

### J. Demandes de produits

Menu **Demandes**.

**Nouvelle demande** :

1. Rechercher par **Nom, code ou code-barres**, bouton **Rechercher**.
2. Choisir l’article.
3. **Nom du client (optionnel)**.
4. **Quantité si elle est connue**.
5. Case **Demande urgente** si besoin. Sans la case, la priorité est **Normale**.
6. **Notes**.
7. **Enregistrer la demande**.

La demande ne change ni le stock ni la caisse.

Statuts : **Ouverte**, **Satisfaite**, **Annulée**.

Sur une demande ouverte, le patron comme l’employé peut :

- **Marquer satisfaite**, avec une **Note de satisfaction (optionnelle)** ;
- **Annuler la demande**, avec un **Motif d’annulation** obligatoire.

Une demande satisfaite ou annulée reste dans la liste. Elle ne peut plus être traitée une seconde fois.

Filtres de la liste : **Article, code, code-barres ou client**, **Statut**, **Priorité**, puis **Filtrer**.

Le lien **Ouvrir** d’une alerte de demande peut ouvrir la liste déjà limitée à l’article concerné, même si ce filtre n’est pas un champ séparé du formulaire.

### K. Alertes

Menu **Alertes**. S’il n’y en a pas : **Aucune alerte pour le moment.**

Chaque alerte a un titre, un texte et un lien **Ouvrir**. Une alerte signale une situation. Elle ne réalise aucune opération : elle ne vend pas, ne transfère pas et ne valide pas.

Alertes actives dans cette version :

| Titre | Signification | Ouvrir mène vers |
| --- | --- | --- |
| Stock faible | Article actif, quantité Boutique entre 1 et le seuil (5 par défaut) | La fiche de l’article |
| Forte demande | Au moins 3 demandes pour le même article sur 30 jours | Les demandes de cet article |
| Faible demande | Article créé depuis plus de 30 jours, encore en Boutique, sans vente conclue sur 90 jours | La fiche de l’article |
| Demandes répétées | Au moins 2 demandes encore ouvertes pour le même article | Les demandes ouvertes de cet article |
| Demande urgente | Demande ouverte cochée urgente | La demande |
| Réception en attente | Un ou plusieurs arrivages en attente | Arrivages en attente |

L’alerte d’échéance de location n’est pas produite dans la V1, puisque les locations ne sont pas utilisées.

Les articles à quantité 0 apparaissent dans **Articles épuisés en boutique** sur le tableau de bord, pas comme stock faible.

### L. Épargne

Menu **Épargne**. Page **Épargne suggérée**.

Calcul réel :

1. Prendre le chiffre d’affaires des ventes **conclues** des 30 derniers jours.
2. Diviser ce total par 30. C’est la moyenne journalière.
3. Prendre 10 % de cette moyenne. C’est le montant suggéré.

L’écran affiche la moyenne, le pourcentage, le chiffre d’affaires de la période et le montant suggéré.

S’il n’y a eu aucune vente conclue sur 30 jours, le message est : « Aucun chiffre d’affaires sur les 30 derniers jours. Aucune épargne n’est suggérée. »

Le texte de la page le rappelle : « Cette suggestion n’est pas automatique. Aucun montant n’est retiré de la caisse. » Il n’y a pas de bouton pour virer cette somme.

### M. Audit

Menu **Audit**. Réservé aux patrons. S’il n’y a rien : **Aucun événement d’audit.**

Filtres : **Action** (liste des actions déjà présentes), **Du**, **Au**, puis **Filtrer**.

Colonnes : **Date**, **Utilisateur**, **Action**, **Sujet**, **Motif**. Si le compte de l’auteur a été supprimé, la colonne utilisateur indique **Compte supprimé**.

On y retrouve notamment les invitations, les ventes, les annulations, les dépenses, les arrivages, les transferts et les ajustements, avec le motif lorsqu’il est obligatoire. Le code d’invitation n’y est jamais écrit.

Ce journal se consulte. Il ne sert pas à corriger une opération.

### N. Profil

Menu du nom, puis **Profil**.

- **Civilité** : Non renseignée, Monsieur ou Madame. Elle n’est jamais déduite de l’adresse e-mail.
- **Prénom**, **Nom**, **Nom affiché**, **Adresse e-mail**, bouton **Enregistrer**.
- **Photo de profil** : JPG, PNG ou WEBP, 2 Mo maximum, 4 000 pixels maximum de côté. **Enregistrer la photo**. **Supprimer** retire la photo. Sans photo, les initiales sont affichées. La photo apparaît à côté du nom dans les actions.
- **Mot de passe** : **Mot de passe actuel**, **Nouveau mot de passe**, **Confirmer le mot de passe**, **Enregistrer**. Le nouveau mot de passe compte au moins 8 caractères.
- **Supprimer le compte** : action définitive, uniquement pour son propre compte, confirmée par le mot de passe.

### O. Sécurité

- Sans activité pendant **5 minutes**, la session se ferme. Il faut se reconnecter. Le travail non enregistré sur un formulaire encore ouvert n’est pas conservé.
- Ne pas partager son mot de passe. Le code d’invitation se transmet une fois, puis la personne choisit son propre mot de passe.
- Ne pas laisser une session ouverte sur un poste partagé. Utiliser **Déconnexion**.
- Un patron voit les coûts, la caisse, les dépenses, l’épargne et l’audit. Ces informations ne doivent pas être recopiées vers un écran visible par un employé ou un client.
- Un patron secondaire ne peut pas inviter un autre patron.
- Personne ne peut inviter ou créer un second patron principal par l’invitation.

### P. Locations

La gestion des locations est reportée. Elle n’est pas disponible dans la version 1. Il n’y a pas de menu, pas de bouton et pas d’alerte d’échéance. Il ne faut pas chercher une page Locations pour le travail quotidien.

---

## Partie C — Manuel de l’employé

L’employé utilise les mêmes pages de connexion, de profil et de déconnexion que le patron. Son tableau de bord et ses menus sont plus courts.

### A. Connexion et profil

Même page **Connexion** : **Adresse e-mail**, **Mot de passe**, **Se connecter**.

Si le patron vient de créer le compte par un code, la première étape n’est pas la connexion. C’est **Activer mon compte** : code à 5 chiffres, mot de passe personnel, confirmation, **Activer le compte**. Ensuite seulement, l’adresse e-mail et ce mot de passe servent à se connecter.

**Profil** permet de changer la civilité, le prénom, le nom, le nom affiché, l’adresse e-mail, la photo et le mot de passe, et de supprimer son propre compte. Les règles de photo et de mot de passe sont les mêmes que pour le patron.

### B. Tableau de bord

L’employé voit l’accueil avec son identité et le slogan.

Il ne voit pas la zone **Pilotage**. Il ne voit pas les ventes du jour, le résultat, la caisse, les dépenses, les classements, le graphique ni le lien vers l’épargne.

Il voit :

- **Stock** : articles actifs, stock Boutique, stock Dépôt, et les articles épuisés en Boutique s’il y en a ;
- **Approvisionnements** : arrivages en attente, accès au nouvel arrivage, derniers arrivages validés et rejetés qui le concernent.

Un montant 0.00 de caisse ne peut pas apparaître chez l’employé : ces chiffres ne lui sont pas envoyés.

### C. Recherche d’articles

Menu **Catalogue**, page **Articles**.

Champ **Nom, code ou code-barres**, **Toutes les catégories**, **Tous les états**, bouton **Filtrer**. Il n’y a pas de bouton **Nouvel article**.

### D. Prix autorisés

L’employé voit le **Prix de vente**. Il ne voit pas le **Prix d'achat**, ni sur la liste, ni sur la fiche, ni au moment d’une vente.

Le prix de vente est affiché, mais le champ est bloqué pour l’employé. Le texte indique : « Vous n’êtes pas autorisé à modifier le prix. » Il n’a pas non plus le bouton **Modifier**.

### E. Boutique et Dépôt

Menu **Stock** : **Vue générale**, **Boutique**, **Dépôt**, **Mouvements**.

La Boutique est le stock vendable. Le Dépôt ne l’est pas. L’employé consulte les quantités et les mouvements. Il ne voit pas, sur la fiche article, les formulaires **Enregistrer l’entrée**, **Transférer** et **Ajuster**.

### F. Enregistrer un arrivage

Menu **Approvisionnements**, **Nouvel arrivage**.

**Article**, **Emplacement**, **Quantité**, **Référence / fournisseur (optionnel)**, puis **Enregistrer l’arrivage**.

Le stock n’augmente pas. Un patron doit valider.

### G. Arrivages et statuts

L’employé ouvre **En attente**, **Historique** et **Tous les arrivages**, mais il ne voit que les arrivages qu’il a lui-même enregistrés.

Statuts : **En attente**, **Validé**, **Rejeté**. Sur un rejet, le motif est visible.

Il n’a pas les boutons **Valider l’arrivage** et **Rejeter l’arrivage**.

### H. Créer une vente

Menu **Ventes**, **Nouvelle vente**.

Même parcours que le patron : rechercher, choisir l’article, saisir la quantité, **Enregistrer la vente**. La ligne montre le prix de vente et le stock Boutique, pas le prix d’achat.

La vente retire la Boutique et encaisse le total. L’employé ne voit pas la caisse, mais l’encaissement a bien lieu.

### I. Recherche par nom, code ou code-barres

Le même champ sert pour le catalogue, la nouvelle vente, le nouvel arrivage et la nouvelle demande. On tape le nom, le code article, ou le code-barres s’il a été enregistré. Il n’y a pas d’appareil photo ni de douchette intégrée : si une douchette est branchée à l’ordinateur et se comporte comme un clavier, elle peut remplir le champ, puis on choisit **Rechercher**.

Si rien ne correspond : **Aucun article ne correspond à cette recherche.**

### J. Vérifier le total

Avant d’enregistrer, vérifier l’article choisi, le prix affiché et la quantité. Le total calculé est quantité × prix de vente. Il est visible après l’enregistrement sur la fiche, ligne **Total**. Il n’y a pas de champ pour changer ce prix pendant la vente.

### K. Historique et détail

Page **Ventes**. L’employé peut filtrer par référence ou article, vendeur, statut, date de début et date de fin, puis ouvrir une vente.

Il voit l’article, la quantité, le prix de vente unitaire, le total, l’emplacement, la date, le statut et le vendeur. Il ne voit pas le coût d’achat ni le résultat. Il ne voit pas le formulaire **Annuler la vente**.

### L. Demandes de produits

Menu **Demandes**, **Nouvelle demande**. Même formulaire que le patron, y compris la case **Demande urgente**.

L’employé peut aussi ouvrir une demande et, tant qu’elle est ouverte, **Marquer satisfaite** ou **Annuler la demande**. Cela ne modifie pas le stock.

### M. Alertes

Menu **Alertes**. L’employé voit les mêmes familles d’alertes que le patron, sauf les échéances de location, qui ne sont pas actives. Les textes ne contiennent pas de montant de caisse, de coût ou de bénéfice.

**Ouvrir** mène vers l’article, la demande ou les arrivages en attente, dans la limite de ce que l’employé a le droit de consulter.

### N. Photo et profil

Même écran **Profil**. Formats JPG, PNG ou WEBP, 2 Mo, 4 000 pixels. **Enregistrer la photo** ou **Supprimer**.

### O. Déconnexion

Menu du nom, en haut à droite, puis **Déconnexion**. Sur téléphone, ouvrir d’abord le menu.

### P. Inactivité

Après **5 minutes** sans action, l’application renvoie vers la page **Connexion**. Il faut se reconnecter. Un formulaire en cours de saisie, pas encore envoyé, est perdu.

### Ce que l’employé ne peut pas faire

- Modifier le prix de vente ou le prix d’achat du catalogue.
- Créer, modifier, désactiver ou réactiver un article.
- Enregistrer une entrée directe, un transfert ou un ajustement de stock.
- Valider ou rejeter un arrivage.
- Voir les arrivages enregistrés par quelqu’un d’autre.
- Annuler une vente.
- Ouvrir la caisse, les dépenses, le résultat, les coûts ou l’épargne.
- Ouvrir le journal d’audit.
- Inviter, révoquer ou gérer les utilisateurs.
- Gérer les locations : la fonction n’est pas disponible dans la V1, et l’employé n’y aurait de toute façon pas accès.

Si une de ces actions est tentée, l’application refuse. Le message habituel est « Action non autorisée. »

---

## Partie D — Procédures pas à pas

### 1. Ajouter un article

**Qui :** patron principal ou patron secondaire.

**Prérequis :** être connecté. Avoir le code, le nom, la catégorie, le prix d’achat et le prix de vente.

**Étapes :**

1. Ouvrir **Catalogue**.
2. Choisir **Nouvel article**.
3. Remplir **Code article**, éventuellement **Code-barres / QR**, **Nom**, **Catégorie**, éventuellement **Description**, **Prix d'achat** et **Prix de vente**.
4. Choisir **Créer l’article**.

**Résultat :** l’article est actif. Son stock Boutique et son stock Dépôt sont à 0. Il apparaît dans le catalogue.

**Erreurs courantes :** code déjà utilisé ; code-barres déjà utilisé ; prix manquant.

**Précautions :** le prix d’achat est obligatoire pour pouvoir vendre plus tard. Sans lui, la vente sera refusée. Le seuil de stock n’est pas à saisir : il est de 5.

### 2. Enregistrer un arrivage

**Qui :** patron ou employé.

**Prérequis :** l’article existe et il est actif.

**Étapes :**

1. Ouvrir **Approvisionnements**, puis **Nouvel arrivage**.
2. Choisir l’**Article** et l’**Emplacement** (Boutique ou Dépôt).
3. Saisir la **Quantité**.
4. Saisir la **Référence / fournisseur** si vous en avez une.
5. Choisir **Enregistrer l’arrivage**.

**Résultat :** l’arrivage est **En attente**. Le stock n’a pas encore changé.

**Erreurs courantes :** article désactivé ; quantité vide ou inférieure à 1.

**Précautions :** un employé ne voit ensuite que ses propres arrivages. Prévenir un patron pour la validation.

### 3. Valider un arrivage

**Qui :** patron principal ou patron secondaire.

**Prérequis :** un arrivage au statut **En attente**.

**Étapes :**

1. Ouvrir **Approvisionnements**, puis **En attente**.
2. Ouvrir l’arrivage.
3. Vérifier l’article, l’emplacement, la quantité et la personne qui a enregistré.
4. Choisir **Valider l’arrivage**.
5. Lire le rappel de quantité, puis **Confirmer la validation**.

Pour rejeter : **Rejeter l’arrivage**, écrire le **Motif obligatoire**, puis **Confirmer le rejet**.

**Résultat :** si validé, le stock de l’emplacement augmente et le statut est **Validé**. Si rejeté, le stock ne change pas et le statut est **Rejeté**.

**Erreurs courantes :** vouloir valider un arrivage déjà traité.

**Précautions :** la validation est définitive. Contrôler l’emplacement : une marchandise validée au Dépôt n’est pas encore vendable.

### 4. Transférer un produit du Dépôt vers la Boutique

**Qui :** patron principal ou patron secondaire. L’employé ne peut pas le faire.

**Prérequis :** l’article est actif et le Dépôt contient au moins la quantité à transférer.

**Étapes :**

1. Ouvrir **Catalogue** et la fiche de l’article.
2. Dans **Transfert dépôt → boutique**, saisir la **Quantité**.
3. Ajouter une **Note** si besoin.
4. Choisir **Transférer**.

**Résultat :** le Dépôt baisse, la Boutique augmente, de la même quantité. Deux mouvements sont enregistrés : Transfert sortant et Transfert entrant.

**Erreurs courantes :** quantité plus grande que le stock Dépôt. Le message indique que le stock du Dépôt est insuffisant.

**Précautions :** ce transfert ne passe pas par la caisse et ne crée pas de vente.

### 5. Réaliser une vente

**Qui :** patron ou employé.

**Prérequis :** article actif, prix d’achat renseigné, quantité suffisante en Boutique.

**Étapes :**

1. Ouvrir **Ventes**, puis **Nouvelle vente**.
2. Saisir le nom, le code ou le code-barres, puis **Rechercher**.
3. Cocher l’article. Vérifier le prix et le stock Boutique.
4. Saisir la **Quantité**.
5. Choisir **Enregistrer la vente**.

**Résultat :** vente **Conclue**, référence VTE suivie d’un numéro, stock Boutique diminué, caisse augmentée du total. Le prix de vente et le prix d’achat du moment sont figés sur la vente.

**Erreurs courantes :** article introuvable ; stock Boutique insuffisant ; prix d’achat non renseigné ; article désactivé ; quantité vide.

**Précautions :** une vente ne prend jamais le stock du Dépôt. Transférer d’abord si la pièce n’est qu’au Dépôt.

### 6. Annuler une vente

**Qui :** patron principal ou patron secondaire. L’employé ne voit pas le bouton.

**Prérequis :** vente au statut **Conclue**, et caisse suffisante pour rendre le montant.

**Étapes :**

1. Ouvrir **Ventes**.
2. Retrouver la vente avec la référence, l’article, le vendeur ou les dates, puis **Filtrer**.
3. Ouvrir la vente.
4. Écrire le **Motif d’annulation**.
5. Choisir **Annuler la vente**.

**Résultat :** statut **Annulée**, stock Boutique restauré, sortie de caisse du même montant, motif conservé.

**Erreurs courantes :** vente déjà annulée ; caisse insuffisante ; motif vide.

**Précautions :** ne pas utiliser **Ajuster** sur la fiche article pour annuler une vente. L’ajustement ne rend pas l’argent et ne change pas le statut de la vente.

### 7. Enregistrer une dépense

**Qui :** patron principal ou patron secondaire.

**Prérequis :** être connecté. Connaître le montant, le motif et la date.

**Étapes :**

1. Ouvrir **Finances**, puis **Dépenses**.
2. Choisir **Nouvelle dépense**.
3. Remplir **Montant**, **Motif** et **Date**.
4. Choisir **Enregistrer**.

**Résultat :** dépense **En attente**, référence DEP. La caisse n’a pas encore baissé.

**Erreurs courantes :** montant vide ou nul ; motif vide.

**Précautions :** l’enregistrement n’est pas un paiement. Il faut encore valider.

### 8. Valider ou refuser une dépense

**Qui :** patron principal ou patron secondaire.

**Prérequis :** dépense **En attente**. Pour une validation, solde de caisse au moins égal au montant.

**Étapes :**

1. Ouvrir **Dépenses**.
2. Filtrer au besoin par **En attente**.
3. Ouvrir la dépense.
4. Pour accepter : **Valider la dépense**.
5. Pour refuser : écrire le motif dans **Refuser avec un motif**, puis **Refuser**.

**Résultat :** statut **Validée** et caisse diminuée, ou statut **Refusée** sans mouvement de caisse. Si le solde est trop faible au moment de la validation, le statut devient **Refusée** avec le texte sur le solde insuffisant.

**Erreurs courantes :** dépense déjà traitée ; motif de refus vide.

**Précautions :** vérifier le **Solde** sur **Caisse** avant de valider une grosse dépense.

### 9. Créer une demande de produit

**Qui :** patron ou employé.

**Prérequis :** l’article existe et il est actif. Un client a demandé quelque chose qui n’a pas été vendu tout de suite.

**Étapes :**

1. Ouvrir **Demandes**, puis **Nouvelle demande**.
2. Rechercher l’article et le choisir.
3. Indiquer le **Nom du client** si vous le connaissez.
4. Indiquer la **Quantité** si elle est connue.
5. Cocher **Demande urgente** seulement si c’est le cas.
6. Ajouter des **Notes** si besoin.
7. Choisir **Enregistrer la demande**.

**Résultat :** demande **Ouverte**, priorité **Normale** ou **Urgente**. Ni le stock ni la caisse ne changent.

**Erreurs courantes :** aucun article choisi ; article désactivé.

**Précautions :** une demande n’est pas une vente et n’est pas une réservation de stock.

### 10. Inviter un utilisateur par code

**Qui :** patron. Seul le patron principal peut inviter un patron secondaire. Un employé ne peut pas inviter.

**Prérequis :** l’adresse e-mail n’est pas déjà celle d’un compte, et il n’y a pas déjà une invitation en attente pour cette adresse.

**Étapes :**

1. Ouvrir **Utilisateurs**.
2. Choisir **Inviter un utilisateur**.
3. Remplir **Prénom**, **Nom**, **E-mail**, **Civilité** et **Rôle**.
4. Choisir **Créer l’invitation**.
5. Lire le code à 5 chiffres dans **Code d’activation à transmettre**.
6. Le communiquer tout de suite à la personne, avec l’adresse du site et l’e-mail saisi.
7. Ne pas compter sur un e-mail automatique : il n’est pas envoyé.

**Résultat :** invitation **En attente**, valable 7 jours, à usage unique.

**Erreurs courantes :** quitter la page avant d’avoir noté le code ; choisir un rôle de patron alors qu’on est patron secondaire.

**Précautions :** le code ne sera plus réaffiché. S’il est perdu, **Révoquer** puis créer une nouvelle invitation.

### 11. Activer un compte invité

**Qui :** la personne invitée, qui n’est pas encore connectée.

**Prérequis :** le code à 5 chiffres, encore valide, et l’adresse de l’application.

**Étapes :**

1. Ouvrir la page **Connexion**.
2. Choisir **Activer mon compte**.
3. Saisir le **Code d’invitation**, y compris les zéros du début.
4. Choisir un **Mot de passe** d’au moins 8 caractères et le confirmer.
5. Choisir **Activer le compte**.

**Résultat :** le compte est créé avec le rôle, la civilité et l’e-mail décidés par le patron. La personne arrive sur le tableau de bord. Le code ne fonctionne plus. Les connexions suivantes utilisent l’e-mail et le mot de passe choisi.

**Erreurs courantes :** code expiré, déjà utilisé, révoqué ou mal recopié. Le message est toujours « Ce code d’invitation n’est pas valable. » Trop d’essais bloque la page pendant un moment.

**Précautions :** le code n’est pas le mot de passe. Ne pas le réutiliser pour se connecter.

### 12. Consulter un rapport

**Qui :** patron principal ou patron secondaire. Il n’y a pas de page Rapports séparée.

**Prérequis :** être connecté avec un compte patron.

**Étapes :**

1. Ouvrir **Tableau de bord**.
2. Lire la zone **Pilotage**.
3. Choisir **Jour**, **Semaine**, **Mois** ou **Année** au-dessus du **Graphique des ventes**.
4. Lire les cartes de ventes, de résultat, de caisse, de dépenses et de stock faible, puis les deux classements.

**Résultat :** les chiffres affichés correspondent aux ventes conclues et aux dépenses validées de la période. Un total nul s’affiche 0.00. L’absence de ventes s’affiche par « Aucune vente enregistrée. » dans les classements.

**Erreurs courantes :** chercher un menu Rapports ; s’attendre à voir les ventes annulées dans le chiffre d’affaires.

**Précautions :** l’employé n’a pas cette zone. Ne pas lui communiquer ces montants par une capture d’écran si ce n’est pas nécessaire.

### 13. Consulter le journal d’audit

**Qui :** patron principal ou patron secondaire.

**Prérequis :** être connecté avec un compte patron.

**Étapes :**

1. Ouvrir **Audit**.
2. Choisir éventuellement une **Action**, une date **Du** et une date **Au**.
3. Choisir **Filtrer**.
4. Lire la date, l’utilisateur, l’action, le sujet et le motif.

**Résultat :** la liste des traces enregistrées. Un journal vide affiche **Aucun événement d’audit.**

**Erreurs courantes :** chercher le code d’invitation dans le journal. Il n’y est pas, volontairement.

**Précautions :** le journal ne corrige rien. Pour annuler une vente ou refuser une dépense, utiliser l’écran de cette opération.

---

## Partie E — Questions fréquentes et dépannage

### Impossible de se connecter

Vérifier l’adresse e-mail, le mot de passe, et que le compte a bien été activé avec un code. Le message « These credentials do not match our records. » signifie que la paire e-mail / mot de passe est refusée.

Si le message parle de « Too many login attempts », attendre la fin du délai avant de réessayer.

Un compte qui n’a jamais été activé ne se connecte pas avec le code. Il faut d’abord passer par **Activer mon compte**.

Le patron peut vérifier dans **Utilisateurs** si l’invitation est encore **En attente**, **Expirée**, **Révoquée** ou **Acceptée**. Il ne peut pas lire ni changer le mot de passe de la personne. Mot de passe oublié après activation : intervention technique, car l’e-mail de réinitialisation n’est pas envoyé dans cette version.

### Session expirée

Après 5 minutes sans activité, l’écran revient à la connexion. Se reconnecter. Ressaisir ce qui n’avait pas été enregistré par un bouton.

### Code d’invitation invalide

Le message est « Ce code d’invitation n’est pas valable. » Vérifier les 5 chiffres, y compris les zéros. Si le patron a quitté la page, il ne peut plus relire le code : il révoque l’invitation encore en attente et en crée une nouvelle.

Cinq essais incorrects en 15 minutes bloquent temporairement la page. Attendre, puis utiliser le bon code ou un nouveau code.

### Code expiré

La durée est de 7 jours à partir de la création. Le patron crée une nouvelle invitation pour la même adresse. Si une invitation est encore en attente, il la révoque d’abord. Une invitation seulement expirée n’empêche pas d’en créer une nouvelle.

### Compte désactivé

Il n’existe pas de bouton « désactiver un utilisateur ». Un employé qui ne peut plus se connecter a en général un mauvais mot de passe, une invitation non activée, ou un compte supprimé par lui-même. La suppression de son propre compte est définitive. La recréation passe par une nouvelle invitation, si l’adresse e-mail est libre.

### Article introuvable

Élargir la recherche : nom, code, ou code-barres. Vérifier le filtre **Désactivés** : un article désactivé n’apparaît pas dans une vente ni dans un arrivage. Vérifier aussi **Toutes les catégories**.

### Stock insuffisant

La vente et la diminution d’un ajustement regardent la quantité de l’emplacement concerné. Pour une vente, c’est seulement la Boutique. Réduire la quantité, ou transférer depuis le Dépôt si le patron confirme que la marchandise y est.

### Produit au Dépôt mais absent de la Boutique

C’est normal. Le Dépôt n’est pas vendable. Un patron ouvre la fiche de l’article et utilise **Transfert dépôt → boutique**.

### Arrivage en attente

Le stock n’a pas encore bougé. Un patron ouvre **En attente**, puis valide ou rejette. L’employé ne peut pas le faire lui-même.

### Vente refusée

Causes prévues par l’application :

- article désactivé ;
- prix d’achat non renseigné : un patron complète **Prix d'achat** sur l’article ;
- quantité supérieure au stock Boutique ;
- quantité invalide.

La vente refusée ne change ni le stock ni la caisse.

### Annulation interdite

L’employé n’a pas le bouton. Un patron ne peut pas annuler une vente déjà **Annulée**. Si la caisse est trop basse pour rendre le montant, l’annulation est refusée : il faut d’abord comprendre le solde, pas modifier le stock à la main.

### Caisse insuffisante

Le solde ne peut pas devenir négatif. Une dépense que l’on tente de valider est alors refusée, avec le texte « Solde de caisse insuffisant. La dépense est refusée et la caisse n’est pas débitée. » Une annulation de vente est aussi bloquée si le montant ne peut pas sortir.

Ce n’est pas une panne. Il faut regarder **Caisse** : ventes, annulations déjà faites, dépenses déjà validées.

### Dépense refusée

Soit un patron a choisi **Refuser** et a écrit un motif, soit la validation a échoué par solde insuffisant. Le motif est sur la fiche, ligne **Décision**. La caisse n’a pas baissé. On peut enregistrer une nouvelle dépense plus tard. On ne peut pas rouvrir la même.

### Photo refusée

La photo doit être une image JPG, PNG ou WEBP, de 2 Mo au plus, et d’au plus 4 000 pixels de côté. Les messages possibles sont : « Choisissez une photo. », « Le fichier doit être une image. », « Formats autorisés : JPG, PNG ou WEBP. », « La photo ne doit pas dépasser 2 Mo. », « L’image est trop grande (4 000 pixels maximum). »

### Alerte stock faible

Elle concerne un article actif dont la Boutique contient entre 1 et 5 unités, 5 étant le seuil de cette version. Ce n’est pas une rupture. La rupture, quantité 0, est listée à part dans **Articles épuisés en boutique**. L’alerte ne commande rien. **Ouvrir** mène à la fiche pour décider d’un transfert ou d’un arrivage.

### Écran vide ou presque vide

- Classement : **Aucune vente enregistrée.**
- Arrivages du tableau de bord : **Aucune donnée**
- Caisse : **Aucun mouvement de caisse.**
- Audit : **Aucun événement d’audit.**
- Alertes : **Aucune alerte pour le moment.**
- Invitations : **Aucune invitation.**
- Demandes : **Aucune demande enregistrée.**
- Épargne : aucune suggestion s’il n’y a pas eu de chiffre d’affaires sur 30 jours.

Un total **0.00** est un résultat calculé, pas un écran cassé.

Si toute l’application reste sur le logo plus de quelques secondes, ou si la page est blanche après le logo, vérifier la connexion au réseau puis recharger. Si le problème continue, c’est une intervention technique.

### Problème de connexion réseau

Sans réseau, la page ne s’ouvre pas ou une action reste sans confirmation. Vérifier la connexion, puis recommencer l’action. Ne pas enregistrer deux fois une vente ou une dépense avant d’avoir vu le message de confirmation ou la nouvelle ligne dans la liste : l’opération a peut-être abouti malgré l’affichage.

### Vente annulée ou correction exceptionnelle

| | Annuler une vente | Ajustement exceptionnel |
| --- | --- | --- |
| Où | Fiche de la vente, bouton **Annuler la vente** | Fiche de l’article, formulaire **Ajustement exceptionnel** |
| Qui | Patron | Patron |
| Motif | Texte libre obligatoire | Liste de motifs, précision obligatoire si « Autre » |
| Stock | La quantité vendue revient en Boutique | La quantité choisie augmente ou diminue l’emplacement choisi |
| Caisse | Le total sort de la caisse | Aucun mouvement |
| Vente | Le statut devient **Annulée** | Aucune vente n’est modifiée |
| Historique | Mouvement **Restauration de vente** | Mouvement **Ajustement** |

### Qui règle le problème

Le patron peut : révoquer et recréer une invitation, corriger un article, valider un arrivage, transférer du stock, annuler une vente si la caisse le permet, valider ou refuser une dépense, lire l’audit.

Une intervention technique est nécessaire pour : l’application qui ne s’ouvre pas, une page blanche durable, un mot de passe oublié alors que le compte est déjà activé, un compte supprimé par erreur, ou un doute sur des données que les écrans ne permettent pas de corriger. Il ne faut pas demander à quelqu’un d’écrire directement dans la base de données.

---

## Partie F — Glossaire

**Boutique**  
Emplacement dont le stock peut être vendu.

**Dépôt**  
Emplacement de réserve. Son stock n’est pas vendu directement.

**Stock disponible**  
Pour une vente, la quantité en Boutique. La quantité au Dépôt n’est pas disponible à la vente.

**Mouvement de stock**  
Trace d’un changement de quantité : entrée, transfert, vente, restauration après annulation, ou ajustement. On le consulte dans **Stock**, **Mouvements**.

**Transfert**  
Déplacement d’une quantité du Dépôt vers la Boutique. Le total des deux emplacements ne change pas.

**Arrivage**  
Déclaration d’une marchandise reçue. Elle reste en attente tant qu’un patron ne l’a pas validée ou rejetée.

**Validation**  
Décision d’un patron qui augmente le stock de la quantité de l’arrivage, ou qui accepte une dépense et débite la caisse.

**Vente**  
Sortie d’une quantité de la Boutique, au prix de vente du moment, avec encaissement du total. Une vente concerne un seul article.

**Annulation**  
Annulation d’une vente conclue, avec motif, retour du stock en Boutique et sortie du montant de la caisse.

**Caisse**  
Solde de l’argent suivi par l’application. Il augmente avec les ventes conclues et diminue avec les annulations et les dépenses validées.

**Dépense**  
Sortie d’argent proposée, puis validée ou refusée. Elle n’est retirée de la caisse qu’à la validation, si le solde suffit.

**Bénéfice**  
Dans l’application, l’écran parle de **Résultat**. C’est le total de la vente moins le coût d’achat figé au moment de la vente. Sur le tableau de bord, c’est la somme de ces résultats pour les ventes conclues de la période.

**Chiffre d’affaires**  
Somme des totaux des ventes conclues. Les ventes annulées n’y entrent pas.

**Demande**  
Aussi appelée demande de produit. Elle note qu’un client a demandé un article. Elle ne réserve pas le stock et ne crée pas de vente. Les priorités sont Normale et Urgente. Les statuts sont Ouverte, Satisfaite et Annulée.

**Alerte**  
Message calculé à l’ouverture de la page Alertes. Ce n’est pas une opération déjà réalisée.

**Audit**  
Journal des actions importantes : qui, quoi, quand, et le motif lorsqu’il a été saisi. Il ne contient pas le code d’invitation.

**Instantané de prix**  
Prix de vente unitaire et coût d’achat unitaire copiés sur la vente au moment où elle est enregistrée. Ils ne suivent pas les changements ultérieurs du catalogue.

**Code d’invitation**  
Cinq chiffres, valables 7 jours, à usage unique, remis par le patron. Ils servent une seule fois à activer le compte et à choisir un mot de passe. Ils ne servent pas à se connecter ensuite.

**Stock faible**  
Article actif dont la quantité en Boutique est comprise entre 1 et le seuil. Dans cette version, le seuil est 5. Il n’est pas réglable dans l’écran. La quantité 0 est un article épuisé, pas un stock faible.

**Location**  
Fonction prévue dans l’application mais non disponible dans la version 1. Elle n’a pas de menu.
