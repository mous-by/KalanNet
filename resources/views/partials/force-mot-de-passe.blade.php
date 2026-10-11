{{--
    Indicateur de force du mot de passe : barre colorée (%) et règles cochées.
    S'attache à tout champ <input data-force-mdp>. Règles obligatoires (comme le
    serveur) : 8 caractères, des lettres, des chiffres.
--}}
@php
    $i18nForceMdp = [
        'niveaux' => [__('securite.force_tres_faible'), __('securite.force_faible'), __('securite.force_moyen'), __('securite.force_fort')],
        'regles' => [__('securite.regle_longueur'), __('securite.regle_lettres'), __('securite.regle_chiffres')],
        'titre' => __('securite.force_titre'),
    ];
@endphp
<style>
    .force-mdp { margin-top: .5rem; }
    .force-mdp .progress { height: 8px; background: #e9ecef; }
    .force-mdp .progress-bar { transition: width .25s ease, background-color .25s ease; }
    .force-mdp-entete { display: flex; justify-content: space-between; font-size: .8rem; margin-bottom: .25rem; }
    .force-mdp-regles { list-style: none; padding: 0; margin: .4rem 0 0; font-size: .8rem; display: flex; flex-wrap: wrap; gap: .25rem .9rem; }
    .force-mdp-regles li { color: #6c757d; }
    .force-mdp-regles li.ok { color: #198754; }
    .force-mdp-regles li i { margin-right: .2rem; }
    @media (prefers-reduced-motion: reduce) { .force-mdp .progress-bar { transition: none; } }
</style>
<script>
    (function () {
        const i18n = @json($i18nForceMdp);
        const couleurs = ['#dc3545', '#fd7e14', '#ffc107', '#198754'];

        function evaluer(mdp) {
            const regles = [mdp.length >= 8, /\p{L}/u.test(mdp), /\d/.test(mdp)];
            let score = 0;
            if (mdp.length >= 8) score += 25;
            if (mdp.length >= 12) score += 15;
            if (/\p{L}/u.test(mdp)) score += 15;
            if (/\d/.test(mdp)) score += 20;
            if (/\p{Ll}/u.test(mdp) && /\p{Lu}/u.test(mdp)) score += 15;
            if (/[^\p{L}\d]/u.test(mdp)) score += 10;
            // Une règle obligatoire manque : jamais au-delà de « Faible ».
            if (regles.includes(false)) score = Math.min(score, 45);
            if (!mdp) score = 0;
            const niveau = score < 30 ? 0 : score < 60 ? 1 : score < 80 ? 2 : 3;
            return { score: Math.min(score, 100), niveau, regles };
        }

        function brancher(champ) {
            if (champ.dataset.forceMdpPret) return;
            champ.dataset.forceMdpPret = '1';
            const bloc = document.createElement('div');
            bloc.className = 'force-mdp';
            bloc.innerHTML =
                '<div class="force-mdp-entete"><span>' + i18n.titre + '</span><strong class="force-mdp-libelle" aria-live="polite"></strong></div>' +
                '<div class="progress" role="progressbar" aria-label="' + i18n.titre + '" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar" style="width:0%"></div></div>' +
                '<ul class="force-mdp-regles">' + i18n.regles.map((r) => '<li><i class="bi bi-circle"></i>' + r + '</li>').join('') + '</ul>';
            (champ.closest('.input-group') || champ).insertAdjacentElement('afterend', bloc);

            const barre = bloc.querySelector('.progress-bar');
            const conteneur = bloc.querySelector('.progress');
            const libelle = bloc.querySelector('.force-mdp-libelle');
            const items = bloc.querySelectorAll('.force-mdp-regles li');

            function maj() {
                const { score, niveau, regles } = evaluer(champ.value);
                barre.style.width = score + '%';
                barre.style.backgroundColor = couleurs[niveau];
                conteneur.setAttribute('aria-valuenow', String(score));
                libelle.textContent = champ.value ? i18n.niveaux[niveau] + ' (' + score + ' %)' : '';
                libelle.style.color = couleurs[niveau];
                regles.forEach((ok, i) => {
                    items[i].classList.toggle('ok', ok);
                    items[i].querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                });
            }
            champ.addEventListener('input', maj);
            maj();
        }

        function initialiser() { document.querySelectorAll('input[data-force-mdp]').forEach(brancher); }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialiser); else initialiser();
    })();
</script>
