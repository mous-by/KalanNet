/*
 * Mise à jour automatique du site : la page compare sa version (balise « kalannet-version ») à celle du serveur, toutes
 * les 5 minutes et à chaque retour sur l'onglet. Nouvelle version : rechargement automatique quand aucune saisie n'est
 * en cours et que la page n'a pas été touchée depuis une minute ; sinon un bandeau propose de mettre à jour, et la
 * prochaine page ouverte sera de toute façon la nouvelle version. Une saisie n'est jamais perdue.
 */
(function () {
    var meta = document.querySelector('meta[name="kalannet-version"]');
    if (!meta || !window.fetch) return;
    var versionPage = meta.content;
    var url = meta.dataset.url;
    var intervalle = parseInt(meta.dataset.intervalle || '300000', 10);
    var repos = parseInt(meta.dataset.repos || '60000', 10);
    var nouvelle = null;
    var saisieEnCours = false;
    var derniereActivite = Date.now();

    var memo = {
        lire: function (cle) { try { return sessionStorage.getItem(cle); } catch (e) { return null; } },
        ecrire: function (cle, valeur) { try { sessionStorage.setItem(cle, valeur); } catch (e) {} },
        oublier: function (cle) { try { sessionStorage.removeItem(cle); } catch (e) {} }
    };

    // Saisie en cours : un champ modifié dans un formulaire qui n'a pas encore été envoyé.
    document.addEventListener('input', function (e) {
        if (e.target && e.target.closest && e.target.closest('form')) saisieEnCours = true;
    }, true);
    document.addEventListener('submit', function () { saisieEnCours = false; }, true);
    ['mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (type) {
        document.addEventListener(type, function () { derniereActivite = Date.now(); }, { passive: true, capture: true });
    });

    function bandeau() {
        if (document.getElementById('kalannet-maj')) return;
        var div = document.createElement('div');
        div.id = 'kalannet-maj';
        div.setAttribute('role', 'status');
        div.className = 'alert alert-info shadow d-flex flex-wrap align-items-center gap-2 mb-0';
        div.style.cssText = 'position:fixed;left:50%;transform:translateX(-50%);bottom:calc(16px + env(safe-area-inset-bottom, 0px));z-index:2000;max-width:calc(100% - 32px);';
        div.innerHTML = '<span><i class="bi bi-arrow-repeat me-1"></i>Une nouvelle version de KalanNet est disponible.</span>'
            + '<button type="button" class="btn btn-sm btn-primary">Mettre à jour maintenant</button>';
        div.querySelector('button').addEventListener('click', function () {
            if (saisieEnCours && !window.confirm('Des informations saisies sur cette page ne sont pas encore enregistrées. Mettre à jour quand même ?')) return;
            recharger();
        });
        document.body.appendChild(div);
    }

    function recharger() {
        // Garde-fou : si la page rechargée annonce encore l'ancienne version, on ne recharge pas en boucle.
        memo.ecrire('kalannet_maj_vers', nouvelle);
        memo.ecrire('kalannet_maj_faite', '1');
        window.location.reload();
    }

    function tenter() {
        if (!nouvelle) return;
        var dejaTente = memo.lire('kalannet_maj_vers') === nouvelle;
        if (dejaTente) { bandeau(); return; }
        if (document.hidden || (!saisieEnCours && Date.now() - derniereActivite >= repos)) {
            recharger();
        } else {
            bandeau();
        }
    }

    function verifier() {
        fetch(url, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (d && d.version && d.version !== versionPage) {
                    nouvelle = d.version;
                    tenter();
                }
            })
            .catch(function () {});
    }

    // Retour après une mise à jour : on le dit, une fois.
    if (memo.lire('kalannet_maj_faite') === '1' && memo.lire('kalannet_maj_vers') === versionPage) {
        memo.oublier('kalannet_maj_faite');
        memo.oublier('kalannet_maj_vers');
        var ok = document.createElement('div');
        ok.className = 'alert alert-success shadow mb-0';
        ok.setAttribute('role', 'status');
        ok.style.cssText = 'position:fixed;left:50%;transform:translateX(-50%);bottom:calc(16px + env(safe-area-inset-bottom, 0px));z-index:2000;max-width:calc(100% - 32px);';
        ok.innerHTML = '<i class="bi bi-check-circle me-1"></i>KalanNet a été mis à jour.';
        document.body.appendChild(ok);
        setTimeout(function () { ok.remove(); }, 5000);
    }

    setInterval(verifier, intervalle);
    setInterval(tenter, Math.min(repos, 30000));
    document.addEventListener('visibilitychange', function () { if (!document.hidden) verifier(); else tenter(); });
    window.addEventListener('online', verifier);
})();
