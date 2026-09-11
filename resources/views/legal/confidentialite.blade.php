<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Politique de confidentialité — KalanNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f7f2;
            --surface: #ffffff;
            --surface-raised: #ffffff;
            --text: #1c2620;
            --text-muted: #5b6b5f;
            --border: rgba(28,38,32,.12);
            --accent: #1f8a4c;
            --accent-soft: rgba(31,138,76,.10);
            --accent-yellow: #b8860b;
            --accent-red: #c0392b;
            --toc-bg: #eef1ea;
            --shadow: 0 1px 2px rgba(28,38,32,.04);
            --font-display: 'Source Serif 4', Georgia, 'Times New Roman', serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #101a14; --surface: #16211a; --surface-raised: #1b2820;
                --text: #e7ede8; --text-muted: #94a89b; --border: rgba(255,255,255,.09);
                --accent: #4ade80; --accent-soft: rgba(74,222,128,.12);
                --accent-yellow: #f2c94c; --accent-red: #f28b82;
                --toc-bg: #14201a; --shadow: 0 1px 2px rgba(0,0,0,.3);
            }
        }
        :root[data-theme="dark"] {
            --bg: #101a14; --surface: #16211a; --surface-raised: #1b2820;
            --text: #e7ede8; --text-muted: #94a89b; --border: rgba(255,255,255,.09);
            --accent: #4ade80; --accent-soft: rgba(74,222,128,.12);
            --accent-yellow: #f2c94c; --accent-red: #f28b82;
            --toc-bg: #14201a; --shadow: 0 1px 2px rgba(0,0,0,.3);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: var(--font-body); line-height: 1.6; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px 80px; }
        header.top { padding: 48px 0 32px; border-bottom: 1px solid var(--border); margin-bottom: 40px; }
        .wordmark { font-family: var(--font-display); font-weight: 700; font-size: 15px; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 18px; }
        .wordmark span:nth-child(1) { color: var(--accent); }
        .wordmark span:nth-child(2) { color: var(--accent-yellow); }
        .wordmark span:nth-child(3) { color: var(--accent-red); }
        h1.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(30px, 4vw, 42px); line-height: 1.15; margin: 0 0 10px; text-wrap: balance; }
        .subtitle { color: var(--text-muted); font-size: 16px; max-width: 62ch; margin: 0; }
        .meta-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 18px; font-size: 13px; color: var(--text-muted); }
        .meta-pill { display: inline-flex; align-items: center; gap: 6px; background: var(--accent-soft); color: var(--accent); border-radius: 999px; padding: 5px 12px; font-weight: 600; font-size: 12.5px; }
        .layout { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 56px; align-items: start; }
        nav.toc { position: sticky; top: 32px; background: var(--toc-bg); border: 1px solid var(--border); border-radius: 14px; padding: 18px 16px; }
        nav.toc .toc-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; padding: 0 6px; }
        nav.toc a { display: block; padding: 7px 8px; border-radius: 8px; font-size: 13.5px; color: var(--text); text-decoration: none; }
        nav.toc a:hover { background: var(--surface-raised); }
        nav.toc ol { list-style: none; margin: 0; padding: 0; }
        main { min-width: 0; }
        section.doc-section { padding: 30px 0; border-bottom: 1px solid var(--border); scroll-margin-top: 24px; }
        section.doc-section:last-child { border-bottom: none; }
        h2 { font-family: var(--font-display); font-weight: 600; font-size: 22px; margin: 0 0 14px; display: flex; align-items: baseline; gap: 10px; }
        h2 .num { font-family: var(--font-body); font-weight: 700; font-size: 13px; color: var(--accent); background: var(--accent-soft); border-radius: 6px; padding: 3px 8px; }
        h3 { font-family: var(--font-body); font-weight: 600; font-size: 15px; margin: 20px 0 8px; color: var(--text); }
        p { margin: 0 0 14px; color: var(--text); font-size: 15px; }
        p.muted { color: var(--text-muted); }
        ul.clean { margin: 0 0 14px; padding-left: 20px; }
        ul.clean li { margin-bottom: 8px; font-size: 15px; }
        ul.clean li b { font-weight: 600; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; box-shadow: var(--shadow); margin: 18px 0; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin: 18px 0; }
        .card h4 { margin: 0 0 6px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .card p { margin: 0; font-size: 13.5px; color: var(--text-muted); }
        .card .icon { width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; background: var(--accent-soft); border-radius: 7px; font-size: 14px; flex-shrink: 0; }
        .responsibility { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 18px 0; }
        .responsibility .card { margin: 0; }
        .responsibility .role { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); margin-bottom: 8px; }
        footer.doc-footer { margin-top: 48px; padding-top: 28px; border-top: 1px solid var(--border); color: var(--text-muted); font-size: 13.5px; }
        footer.doc-footer a { color: var(--accent); }
        @media (max-width: 860px) {
            .layout { grid-template-columns: 1fr; }
            nav.toc { position: static; }
            .responsibility { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <div class="wordmark"><span>KAL</span><span>AN</span><span>NET</span></div>
        <h1 class="title">Politique de confidentialité</h1>
        <p class="subtitle">Comment KalanNet — plateforme de gestion scolaire (bulletins, présences, paiements, communication) — collecte, utilise et protège les données de ses utilisateurs, sur le web comme sur mobile.</p>
        <div class="meta-row">
            <span class="meta-pill">🔒 Dernière mise à jour : 11 septembre 2026</span>
            <span>kalannet.com · Contact : barrymoustapha908@gmail.com</span>
        </div>
    </header>

    <div class="layout">
        <nav class="toc" aria-label="Sommaire">
            <div class="toc-label">Sommaire</div>
            <ol>
                <li><a href="#qui-sommes-nous">Qui nous sommes</a></li>
                <li><a href="#responsabilites">Répartition des responsabilités</a></li>
                <li><a href="#donnees-collectees">Données collectées</a></li>
                <li><a href="#permissions-mobile">Permissions de l'app mobile</a></li>
                <li><a href="#utilisation">Utilisation des données</a></li>
                <li><a href="#partage">Partage des données</a></li>
                <li><a href="#securite">Sécurité</a></li>
                <li><a href="#conservation">Conservation</a></li>
                <li><a href="#droits">Vos droits</a></li>
                <li><a href="#mineurs">Élèves mineurs</a></li>
                <li><a href="#modifications">Modifications</a></li>
                <li><a href="#contact">Contact</a></li>
            </ol>
        </nav>

        <main>
            <section class="doc-section" id="qui-sommes-nous">
                <h2><span class="num">01</span>Qui nous sommes</h2>
                <p>KalanNet est une plateforme de gestion scolaire (bulletins, présences, notes, paiements, communication interne) utilisée par des établissements scolaires, leur personnel, les parents et — indirectement, via l'établissement — les élèves qu'ils encadrent. Elle est disponible sur le web (kalannet.com) et sous forme d'application mobile.</p>
                <p>L'éditeur de KalanNet est joignable à <b>barrymoustapha908@gmail.com</b> pour toute question relative à cette politique ou à vos données.</p>
            </section>

            <section class="doc-section" id="responsabilites">
                <h2><span class="num">02</span>Répartition des responsabilités</h2>
                <p>KalanNet est un outil fourni aux établissements scolaires : nous n'entrons pas nous-mêmes les données des élèves, du personnel ou des familles — ce sont les établissements qui le font, dans le cadre de leur mission éducative.</p>
                <div class="responsibility">
                    <div class="card">
                        <div class="role">L'établissement scolaire</div>
                        <p style="color:var(--text)">Décide quelles données saisir sur ses élèves, son personnel et les familles, et reste responsable de la légalité de cette collecte (autorisation des familles, durée de conservation, etc.).</p>
                    </div>
                    <div class="card">
                        <div class="role">KalanNet</div>
                        <p style="color:var(--text)">Héberge, sécurise et traite ces données pour le compte de l'établissement, strictement dans le cadre du service demandé (gestion scolaire, abonnement, communication).</p>
                    </div>
                </div>
            </section>

            <section class="doc-section" id="donnees-collectees">
                <h2><span class="num">03</span>Données que nous collectons</h2>

                <h3>Compte utilisateur</h3>
                <p>Nom et prénom, adresse email, numéro de téléphone, mot de passe (stocké haché, jamais en clair), fonction (rôle : administrateur, gestionnaire, enseignant, parent...) et établissement(s) rattaché(s).</p>

                <h3>Photo de profil</h3>
                <p>Facultative — ajoutée par vous-même depuis la galerie ou l'appareil photo de votre téléphone.</p>

                <h3>Données pédagogiques saisies par l'établissement</h3>
                <p>Identité et photo des élèves, classes, notes et évaluations, bulletins, présences et émargements.</p>

                <h3>Données financières</h3>
                <p>Paiements de frais de scolarité enregistrés par l'établissement (montants, dates, mode de règlement) ainsi que les paiements d'abonnement de l'établissement à KalanNet : pour un règlement manuel, cela inclut une preuve de paiement (capture d'écran ou photo du reçu), une référence de transaction et le numéro de téléphone utilisé pour payer (Orange Money, Wave, Mobicash).</p>

                <h3>Données techniques</h3>
                <p>Horodatage de connexion et de dernière activité, jeton de session, et les journaux techniques standards générés par tout service en ligne (adresse IP, type d'appareil).</p>
            </section>

            <section class="doc-section" id="permissions-mobile">
                <h2><span class="num">04</span>Permissions de l'application mobile</h2>
                <p class="muted">L'application ne demande que les permissions nécessaires aux fonctions que vous utilisez explicitement — jamais en arrière-plan.</p>
                <div class="card-grid">
                    <div class="card">
                        <h4><span class="icon">📷</span>Appareil photo / Galerie</h4>
                        <p>Pour ajouter une photo de profil ou joindre la preuve d'un paiement d'abonnement.</p>
                    </div>
                    <div class="card">
                        <h4><span class="icon">💾</span>Stockage</h4>
                        <p>Pour enregistrer ou consulter un bulletin ou un document exporté depuis l'application.</p>
                    </div>
                </div>
            </section>

            <section class="doc-section" id="utilisation">
                <h2><span class="num">05</span>Comment nous utilisons ces données</h2>
                <ul class="clean">
                    <li><b>Fournir le service</b> : authentification, gestion scolaire, suivi des paiements et des abonnements.</li>
                    <li><b>Sécuriser les comptes</b> : limiter l'accès de chaque utilisateur à son rôle et à son établissement — un compte d'une école ne voit jamais les données d'une autre école.</li>
                    <li><b>Communiquer</b> : transmettre les annonces et notifications envoyées par l'établissement à son personnel et aux familles.</li>
                    <li><b>Assurer le support</b> et la maintenance technique de la plateforme.</li>
                </ul>
            </section>

            <section class="doc-section" id="partage">
                <h2><span class="num">06</span>Partage des données</h2>
                <p><b>KalanNet ne vend ni ne loue aucune donnée à des tiers.</b> Les données ne sont partagées qu'avec :</p>
                <ul class="clean">
                    <li>Notre hébergeur, pour le fonctionnement technique du service ;</li>
                    <li>Les opérateurs de paiement mobile (Orange Money, Wave, Mobicash), uniquement lors du règlement d'un abonnement et dans la stricte mesure nécessaire à la transaction ;</li>
                    <li>Les autorités compétentes, si la loi l'exige.</li>
                </ul>
            </section>

            <section class="doc-section" id="securite">
                <h2><span class="num">07</span>Sécurité</h2>
                <ul class="clean">
                    <li>Mots de passe stockés sous forme hachée, jamais en clair.</li>
                    <li>Accès aux données cloisonné par rôle et par établissement.</li>
                    <li>Connexions chiffrées (HTTPS) entre l'application et nos serveurs.</li>
                </ul>
            </section>

            <section class="doc-section" id="conservation">
                <h2><span class="num">08</span>Conservation des données</h2>
                <p>Les données sont conservées aussi longtemps que le compte de l'établissement reste actif sur KalanNet. Sur demande de l'établissement ou d'un utilisateur, les données concernées peuvent être supprimées, sous réserve des obligations légales de conservation (comptabilité, notamment).</p>
            </section>

            <section class="doc-section" id="droits">
                <h2><span class="num">09</span>Vos droits</h2>
                <p>Vous pouvez demander l'accès, la rectification ou la suppression de vos données. Pour les données pédagogiques saisies par votre établissement (notes, présences, dossier élève), la demande transite en priorité par lui, puisqu'il en reste responsable. Vous pouvez aussi nous écrire directement pour être orienté : <b>barrymoustapha908@gmail.com</b>.</p>
            </section>

            <section class="doc-section" id="mineurs">
                <h2><span class="num">10</span>Élèves mineurs</h2>
                <p>KalanNet ne collecte pas directement de données auprès des élèves mineurs via l'application. Ce sont les établissements et leur personnel qui saisissent et gèrent ces informations, dans le cadre de leur mission éducative et sous leur responsabilité.</p>
            </section>

            <section class="doc-section" id="modifications">
                <h2><span class="num">11</span>Modifications de cette politique</h2>
                <p>Cette page peut être mise à jour ; la date figurant en haut de page reflète la dernière révision.</p>
            </section>

            <section class="doc-section" id="contact" style="border-bottom:none;">
                <h2><span class="num">12</span>Contact</h2>
                <p>Pour toute question sur cette politique ou vos données : <b>barrymoustapha908@gmail.com</b>.</p>
            </section>

            <footer class="doc-footer">
                KalanNet — plateforme de gestion scolaire.
            </footer>
        </main>
    </div>
</div>
</body>
</html>
