# SBC - Site vitrine + SBC Studio

Monorepo MySQL + Symfony + Next.js pour le site public SBC et son CMS prive "SBC Studio". Les pages existantes et les nouvelles pages sont composables depuis l'interface. L'administration est accessible sur `/atelier-admin`, et la connexion sur `/atelier-admin/login`.

## Stack
- Backend: Symfony 7.4, PHP 8.3+ (image Docker PHP 8.4), Doctrine ORM, Security, Validator, Mailer, Messenger.
- Frontend: Next.js App Router, TypeScript, Tailwind, react-hook-form, zod.
- Base: MySQL 8 (utf8mb4, InnoDB).
- Infra locale: Docker Compose (Nginx, backend, node, mysql).

## Arborescence
- backend: API et logique metier
- frontend: site public + interface Atelier
- docker: configuration Nginx
- scripts: controles de conformite

## Installation locale
1. Copier les environnements:
   - cp backend/.env.example backend/.env.local
   - cp frontend/.env.example frontend/.env.local
2. Lancer l environnement:
   - make start
   - Le frontend tourne en mode developpement avec rechargement a chaud. Le port HTTP commence a `8080`; si le port est occupe ou refuse par Docker, le suivant est essaye. L'URL de demarrage est affichee dans le terminal.
3. Creer la base et le schema dans le conteneur:
  - docker compose exec backend php bin/console doctrine:database:create --if-not-exists
  - docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
  - docker compose exec backend php bin/console doctrine:fixtures:load --no-interaction --purge-exclusions=user
  (sans `--purge-exclusions=user`, les fixtures effacent aussi les comptes Atelier)
  - docker compose exec backend php bin/console app:cms:install
  - docker compose exec backend php bin/console app:home:partners
4. Creer le premier administrateur:
  - docker compose exec backend php bin/console app:create-admin

## URLs
- Site public: http://localhost:8080 (ou le port affiche par `make start`)
- API Symfony: http://localhost:8080/api (ou le meme port affiche par `make start`)
- Atelier prive: `/atelier-admin` (defini par `ADMIN_PATH` et `NEXT_PUBLIC_ADMIN_PATH`)
- MySQL reste prive au reseau Docker, sans port hote expose par defaut.

## Securite admin
- Authentification session cookie httpOnly (Symfony Security).
- Routes admin serveur sous /api/admin/* protegees par roles.
- URL admin publique non listee dans menu, sitemap et robots.
- Header `X-Robots-Tag: noindex, nofollow` applique sur `/atelier-admin` et ses sous-pages. Cette adresse ne remplace pas l'authentification.
- La deconnexion invalide la session et renvoie du JSON a l'interface avant son retour sur `/atelier-admin/login`.

## Atelier : gestion autonome du site
- Le Studio propose des raccourcis par tache et un acces direct a chaque page depuis le tableau de bord. La navigation est recherchable, adaptee au mobile et conserve le module ouvert dans l'URL.
- Les contenus sont presentes sous forme de cartes avec recherche et filtre de publication. L'edition d'une page separe le contenu, l'adresse et les menus, puis le referencement. Une barre d'enregistrement reste accessible pendant l'edition.
- **Pages et navigation** : creer une page, choisir son adresse, son titre et sa description SEO, afficher ou non son lien dans les menus ou les liens complementaires du pied de page, publier ou conserver un brouillon, supprimer definitivement.
- **Composition des pages** : choisir une section dans un catalogue explique, l'ajouter, la dupliquer, la retirer, la masquer ou la reordonner avec les fleches. Sections disponibles : grande introduction, texte avec image, galerie, cartes libres, services, realisations, avis, FAQ, partenaires, certifications, formulaire de devis, coordonnees et annonces. Les cartes et les photos des galeries peuvent aussi etre reordonnees.
- L'onglet **Mise en page** d'une section regle son fond, son espacement, sa largeur et son alignement. Les sections avec photo peuvent etre empilees, partagees ou inversees et conserver l'image entiere. Les collections de cartes proposent de une a quatre colonnes sur ordinateur. Les photos du hero peuvent etre explicitement masquees, sans retour automatique de la photo par defaut.
- La presentation des collections peut aussi changer : cartes ou etapes de methode, resume des familles ou catalogue des prestations, galeries completes des projets meme sur l'accueil. Les choix restent structures pour conserver les interactions et l'adaptation mobile.
- L'apercu utilise le meme rendu que le site, en-tete et pied de page compris, dans un cadre ordinateur ou mobile avec ses propres regles responsive. Il affiche les modifications non enregistrees sans les publier ; les formulaires et les liens y sont desactives. Les changements ne sont appliques qu'avec **Enregistrer**. Une confirmation protege les modifications non enregistrees lors du changement de module.
- Les sections de collections reprennent les elements publies de leur module, dans l'ordre enregistre. Les textes propres aux pages se modifient directement dans leurs sections. Les textes historiques `home.*` ne sont plus la source des pages apres initialisation.
- Variables utilisables dans les textes des sections : `{company_name}`, `{experience_years}`, `{slogan_primary}`, `{company_intervention_area}`. Elles reprennent les reglages au moment de l'affichage.
- **Annonces** : actualites, offres commerciales, emplois et informations, avec texte, photo, lien facultatif, brouillon et dates de diffusion facultatives. Les horaires saisis sont ceux de Paris. Le debut est inclus, la fin exclue. Ajoutez une section Annonces aux pages concernees pour les afficher.
- **Collections** : creation, modification, publication, ordre et suppression pour les services, realisations, avis, questions frequentes, partenaires et certifications. La suppression d'un service retire son association aux realisations sans effacer ces dernieres.
- **Partenaires sur l'accueil** : la page dediee est remplacee par une section en bas de l'accueil. Chaque categorie forme un petit carrousel independant ; les quatre categories initiales reprennent peinture, platrerie/cloisons/isolation, salles de bains/sanitaires et electricite/domotique. Le module Partenaires permet de remplacer les noms Lorem ipsum, choisir les logos, renseigner les liens et changer les categories ou l'ordre. Sans logo, un emplacement graphique est affiche. Seuls les partenaires publies apparaissent.
- Le defilement se suspend au survol, au focus et avec le bouton pause. Il est desactive si le visiteur prefere limiter les animations. Les fleches restent utilisables manuellement ; une categorie avec un seul partenaire ne defile pas.
- **Mediatheque** : import de plusieurs fichiers, description accessible, recherche, affichage des utilisations et filtrage des fichiers inutilises. Le selecteur photo permet aussi d'importer des images sans quitter l'editeur.
- Limite d'import : 30 Mo par fichier, verifiee dans le navigateur et sur le serveur. Les limites PHP et Nginx sont alignees sur cette taille, avec une marge pour le formulaire multipart.
- **Retirer** une photo d'une section ne supprime pas le fichier. **Supprimer definitivement** dans la mediatheque efface l'original et toutes ses variantes du stockage. Un fichier utilise dans une page, une annonce, une collection ou un brouillon ne peut pas etre supprime : ses utilisations sont indiquees pour permettre son retrait prealable. Les fichiers deja telecharges chez un visiteur ne peuvent pas etre revoques.
- **Emplacements photo** : remplacer ou vider une photo partagee. Une section peut choisir une photo propre ou se lier a un emplacement partage. Les changements de cet emplacement se repercutent alors sur toutes les sections liees.
- **Apparence du site** : palette, polices deja installees, largeur du contenu, arrondis, taille du texte, hauteur et partage du hero, densite des sections et visibilite de l'en-tete, du pied de page et des reperes. Les changements peuvent etre previsualises avant enregistrement. Ces choix n'affectent jamais le Studio.
- **Entreprise et coordonnees** : informations publiques, logo, icone du navigateur, bandeau du site, bouton de contact (texte et destination, vide pour masquer), signatures et titres du pied de page, description SEO generale et parametres d'envoi des e-mails.
- **Textes et libelles** : legendes du hero, reperes de confiance, noms et presentations des familles de prestations, bloc photo, filtres, liens des projets, galeries, menus mobiles, carte et libelles, aides et messages de validation du formulaire de devis. Les coordonnees geographiques de la carte se changent via son URL OpenStreetMap ; vider cette URL masque la carte.
- Ces reglages sont decrits et valides dans `SiteEditor`. `GET /api/admin/site-editor` fournit les champs et leurs valeurs effectives, et `PUT` enregistre uniquement les champs modifies, apres validation de l'ensemble. Les valeurs par defaut sont appliquees sans migration ni rechargement des fixtures. Les logos et icones utilises sont proteges contre la suppression en mediatheque.
- **Textes des e-mails** : personnalisation des confirmations et notifications. Un texte enregistre vide reste vide, sans reintroduire son texte par defaut.
- **Demandes de devis** : recherche, filtrage, fiche complete, coordonnees, pieces jointes et changement de statut.
- Les pages publiees alimentent automatiquement les menus et le sitemap. Les modifications sont visibles a la prochaine requete, sans attendre une expiration de cache.
- Tous les textes enregistres par les modules CMS sont normalises pour retirer les tirets interdits. Les liens et les references aux images sont controles cote serveur.
- L'editeur utilise des blocs structures, pas du HTML ou du JavaScript libre. Aucune dependance a un CMS ou a un stockage de medias externe n'est ajoutee. L'envoi des e-mails conserve le transport Resend existant.
- Le constructeur regle les contenus et les presentations prises en charge, pas du code arbitraire. Les regles de validation des demandes, les limites de fichiers, les comptes et la configuration du transport d'e-mails ne se desactivent pas depuis les reglages de presentation. Les champs et limites acceptes sont controles cote serveur.

### Mise a jour d'une installation existante
1. Sauvegarder MySQL et le volume `backend_share` avant toute mise a jour.
2. Reconstruire et demarrer les services : `docker compose up -d --build`.
3. Executer `docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction`.
4. Executer `docker compose exec backend php bin/console app:cms:install`.
5. Executer `docker compose exec backend php bin/console app:home:partners` pour integrer les carrousels et supprimer l'ancienne page Partenaires. Cette operation unique preserve les partenaires existants et ne cree les douze entrees provisoires que si leur collection est vide. Les executions suivantes preservent les modifications du Studio.

Ne pas recharger les fixtures pour effectuer cette mise a jour : elles purgent les donnees. La migration ajoute les tables et colonnes sans effacer les contenus. L'initialisation cree les pages initiales en reprenant les textes et photos existants, sans ecraser les pages deja presentes. Un marqueur interne empeche de recreer ulterieurement des pages volontairement supprimees ou renommees.

### Verifications CMS
- `cd frontend && npm run build`
- `cd frontend && npm run lint`
- `cd frontend && npm run check:dashes`
- `docker compose exec backend php bin/console doctrine:schema:validate`
- `docker compose exec backend php tests/cms-smoke.php`
- `docker compose exec backend php tests/project-photos-smoke.php`

La suite CMS verifie les neuf ressources, les treize types de sections, la publication et l'ordre, les champs de presentation, les medias et leurs protections, les emplacements partages, les textes d'e-mails, les sept statuts de devis et les pieces jointes. Tous les reglages du constructeur font un aller-retour d'enregistrement. Les entrees invalides, les acces non authentifies, les jetons CSRF et la deconnexion sont aussi controles.

Les tests utilisent un compte et des contenus temporaires, puis restaurent les reglages, textes et photos partages. Les devis de controle sont crees sans soumission publique : aucun e-mail reel n'est envoye. La preparation des messages et le lien de notification vers l'admin sont verifies avec un transport de capture, pas avec Resend. La delivrabilite reelle reste un controle distinct.

### Accueil : rythme et parcours
- Installation existante : apres reconstruction du backend, executer `docker compose exec backend php bin/console app:home:compose`.
- Cette operation ponctuelle raccourcit la presentation, place les realisations juste apres elle et masque les deux anciens blocs interieur/exterieur, conserves dans le CMS. Les prestations restent accessibles dans les trois familles de services.
- Le marqueur interne empeche une seconde execution d'ecraser des modifications faites ensuite dans l'Atelier. Les nouvelles installations utilisent directement ce parcours.
- La methode s'affiche en etapes reliees, horizontales sur ordinateur et verticales sur mobile.

Le test HTTP utilise l'URL interne `http://nginx` (modifiable avec `CMS_TEST_BASE_URL`), cree un compte et des contenus temporaires, puis les retire dans un bloc de nettoyage. Il controle l'authentification, le CSRF, les collections, les brouillons, les publications, les annonces programmees, l'ordre, la validation, l'import d'image, la protection des medias utilises et la suppression physique des fichiers.

## Realisations par categorie
- Entite `RealisationCategory`: titre, slug, description, service associe (`tagService`), galerie ordonnee, ordre, statut (brouillon / publie). Aucun champ ville, annee, duree ou surface.
- Fixtures: 3 categories publiees construites depuis `backend/fixtures/photos/` : 4 photos de salles de bains, 1 chambre (`chambre-01.jpeg`) et 2 amenagements (`amenagement-01.jpg`, `amenagement-02.jpg`). Les noms et dossiers corriges de `photos_realisations/` font reference. L'ancien `sdb-05.jpeg` est la chambre ; l'ancien `chambre-01.jpg` est `amenagement-02.jpg`.
- Les quatre salles de bains des fixtures ont ete tournees de 90 degres vers la droite pour retrouver leur orientation verticale. Les fichiers fournis dans `photos_realisations/` ne sont pas modifies. Ne pas appliquer une deuxieme rotation aux fixtures corrigees.
- Installation existante : reconstruire le backend puis executer `docker compose exec backend php bin/console app:photos:sync`. Cette commande ne purge pas la base : elle conserve les identifiants, corrige les fichiers et variantes, reaffecte les sept photos aux bonnes categories et configure `amenagement-01.jpg` comme photo principale. Les photos supplementaires eventuellement ajoutees aux categories sont conservees. Une empreinte des sources evite de regenerer les fichiers lors des executions suivantes. Les anciens fichiers de stockage sont conserves pour ne pas invalider les URL deja distribuees.
- Verification en lecture seule apres synchronisation : `docker compose exec backend php tests/project-photos-smoke.php`.
- Atelier, module Realisations: titre, description, service, galerie (glisser-deposer, ajout par envoi ou depuis la mediatheque), ordre des categories (glisser-deposer), statut. Le texte alternatif de chaque photo se modifie dans la Mediatheque.
- La premiere salle de bains illustre le service Salle de bain ; `amenagement-01.jpg` illustre les meubles de rangement sur mesure. La chambre est associee au service Peinture. L'image de chaque service reste modifiable dans Atelier > Services.
- Traitement des images (upload et fixtures): suppression des metadonnees (EXIF), correction d'orientation, original conserve, variantes WebP en 480, 800, 1200 et 1600 px (jamais au-dela de la taille source), AVIF ajoute automatiquement si l'encodeur est present dans l'image PHP (ce n'est pas le cas de l'image Docker actuelle: WebP seul). Les images sont servies en `<picture>` avec `srcset` via `/api/media/{id}?w=...&f=webp`.
- Le jeu de sept photos est le seul utilise. Le hero garde une largeur contenue, les galeries affichent les photos entieres et aucune section vide ou promesse de futurs visuels n'est montree. Les URL des images incluent une version de cache pour rendre les corrections visibles meme avec le cache immutable des medias.

## E-mails du formulaire de devis (Resend)
- Transport: `symfony/resend-mailer`, DSN `MAILER_DSN=resend+api://${RESEND_API_KEY}@default` dans `backend/.env.local` (non versionne, exclu de l'image Docker). `backend/.env.example` ne contient que le placeholder `RESEND_API_KEY=`.
- **La cle API Resend utilisee pendant le developpement a circule en clair dans une conversation. Elle doit etre regeneree dans le tableau de bord Resend avant la mise en production, puis l'ancienne revoquee.** La nouvelle valeur se place uniquement dans le `.env.local` du serveur, jamais dans Git ni dans un fichier envoye au client.
- Flux: la demande est enregistree en base, puis deux e-mails (confirmation client, notification interne) sont mis en file Messenger (transport Doctrine `async`). Le service Docker `worker` les envoie, avec 4 nouvelles tentatives espacees (5 s, 15 s, 45 s, 135 s). Un echec definitif est ecrit dans le journal d'audit (`mail.failed`, avec le numero de la demande) et le message reste dans la file `failed` (`php bin/console messenger:failed:retry`).
- Notification: envoyee a `sbc.nefzi@gmail.com` (reglage `notification_email`, modifiable dans Atelier > Reglages), avec le resume, la description, le contact, et un lien vers la fiche (`?quote=ID` sur l'URL secrete de l'Atelier). `reply-to` = e-mail du client.
- Expediteur: `onboarding@resend.dev` (reglage `mail_from`) tant qu'aucun domaine n'est verifie. Avec cet expediteur de test, Resend n'accepte d'envoyer qu'a l'adresse du proprietaire du compte: l'e-mail de confirmation a un client quelconque sera refuse tant que le domaine n'est pas verifie.
- A faire plus tard: verifier un domaine du client dans Resend (enregistrements DNS SPF, DKIM et DMARC fournis par Resend), puis remplacer `mail_from` par une adresse de ce domaine (Atelier > Reglages) pour la delivrabilite.
- Gabarits: mise en page dans `backend/templates/email/` (papier, encre, filet terre cuite, meme charte que le site). Sujets et textes modifiables dans Atelier > Pages et textes (cles `email.confirmation.*` et `email.notification.*`; variables `{prenom}`, `{nom}`, `{ville}`).

## Revalidation cache
- Endpoint Next.js signe: POST /internal/revalidate
- Header requis: x-revalidate-secret
- Secret partage via `REVALIDATE_SECRET` (frontend + backend).

## Controle des tirets interdits
- Script: scripts/check-no-cadratin.sh
- Execute via:
  - cd frontend && npm run check:dashes
  - cd backend && composer check-dashes

## Emplacements photo historiques du CMS

Ces emplacements restent disponibles pour compatibilite avec les pages existantes. Ils ne constituent pas une liste de photos a fournir : les emplacements vides ne sont pas affiches sur le site public.
- Accueil
  - PH-ACCUEIL-HERO, amenagement-01.jpg, affichage carre, source 457x470
  - PH-ACCUEIL-INTERIEUR, ratio 4:5, min 1600x2000
  - PH-ACCUEIL-EXTERIEUR, ratio 4:5, min 1600x2000
  - PH-ACCUEIL-METIER-01, ratio 1:1, min 1400x1400
  - PH-ACCUEIL-METIER-02, ratio 1:1, min 1400x1400
  - PH-ACCUEIL-METIER-03, ratio 1:1, min 1400x1400
  - PH-ACCUEIL-BANDEAU-DEVIS, ratio 16:9, min 2400x1350
- Services
  - 1 slot 4:3 par service: cuisine, salle de bain, peinture, carrelage et revetements, doublage et isolation, plomberie et robinetterie, electricite, meubles de rangement, facades, toitures, terrasses, amenagement et agrandissement
- Realisations
  - Plus de PhotoSlot: la page fonctionne par categories (voir section Realisations). L'apercu de l'accueil reprend la premiere photo de chaque categorie publiee.
- A propos
  - portrait dirigeant 4:5
  - equipe 3:2
  - chantier 3:2
  - 2 details 1:1
- Contact
  - 1 ambiance 4:5
- Partenaires
  - 8 logos, ratio 3:2
- Video
  - 1 slot optionnel hero
  - 1 slot optionnel A propos

## Notes de production
- Ce repository fournit une base fonctionnelle complete et extensible.
- Les modules avances (2FA TOTP complet, workflow kanban drag and drop, suppression EXIF asynchrone, statistiques serveur sans cookie) sont prevus dans l architecture et peuvent etre etendus module par module depuis la base presente.
