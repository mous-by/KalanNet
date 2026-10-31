{{-- Fond en carrousel plein écran (photos des modules qui défilent) : page de connexion et pages « invité »
     (changement du mot de passe temporaire). Défilement toutes les 6 s, points, barre de progression, balayage au doigt. --}}
<style>
        /* ══════════════════════════════════
           FULL-SCREEN CAROUSEL BACKGROUND
        ══════════════════════════════════ */
        .bg-carousel {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        .bg-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            z-index: 1;
            transition: opacity 1.6s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .bg-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .bg-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .bg-slide.active img {
            animation: kenBurns 9s ease-in-out forwards;
        }
        @keyframes kenBurns {
            from { transform: scale(1)    translate(0,    0);    }
            to   { transform: scale(1.08) translate(-1%,  -.5%); }
        }

        /* Dark gradient overlay on every slide */
        .bg-slide::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg,
                    rgba(10,18,35,.55) 0%,
                    rgba(10,18,35,.2)  40%,
                    rgba(10,18,35,.45) 75%,
                    rgba(10,18,35,.72) 100%
                ),
                linear-gradient(105deg,
                    rgba(10,18,35,.35) 0%,
                    transparent        55%
                );
        }

        /* Slide caption — bottom-left area */
        .bg-caption {
            position: absolute;
            left: clamp(28px, 4vw, 64px);
            bottom: clamp(110px, 16vh, 180px);
            z-index: 10;
            max-width: min(420px, 42vw);
            pointer-events: none;
        }
        .bg-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--theme-accent);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 999px;
            margin-bottom: 12px;
            letter-spacing: .3px;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .5s .35s, transform .5s .35s;
        }
        .bg-slide.active .bg-badge { opacity: 1; transform: translateY(0); }

        .bg-title {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.22;
            margin-bottom: 10px;
            text-shadow: 0 3px 20px rgba(0,0,0,.5);
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .55s .5s, transform .55s .5s;
        }
        .bg-slide.active .bg-title { opacity: 1; transform: translateY(0); }

        .bg-quote {
            font-size: clamp(.78rem, .9vw, .9rem);
            color: rgba(255,255,255,.8);
            font-style: italic;
            line-height: 1.6;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .55s .65s, transform .55s .65s;
        }
        .bg-slide.active .bg-quote { opacity: 1; transform: translateY(0); }

        /* ── Dot indicators ── */
        .bg-dots {
            position: fixed;
            left: clamp(28px, 4vw, 64px);
            bottom: clamp(72px, 9vh, 110px);
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bg-dot {
            height: 5px;
            border-radius: 3px;
            background: rgba(255,255,255,.38);
            border: none;
            padding: 0;
            cursor: pointer;
            width: 5px;
            transition: width .4s ease, background .4s ease;
        }
        .bg-dot.active { width: 26px; background: #fff; }

        /* ── Progress bar ── */
        .bg-progress {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            z-index: 20;
            background: rgba(255,255,255,.15);
        }
        .bg-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--theme-accent), rgba(255,255,255,.7));
            width: 0%;
        }

        @media (max-width: 600px) {
            .bg-caption { display: none; }
            /* Points masqués : le balayage au doigt suffit */
            .bg-dots { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .bg-slide, .bg-slide img, .bg-progress-bar { transition: none !important; }
        }
</style>

{{-- ════ FULL-SCREEN CAROUSEL BACKGROUND ════ --}}
<div class="bg-carousel" id="bgCarousel" aria-hidden="true">

    <div class="bg-slide active" data-index="0">
        <img src="{{ asset('assets/images/fond1.png') }}" alt="" loading="eager">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-mortarboard-fill me-1"></i>{{ __('messages.modules.students') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide1_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide1_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="1">
        <img src="{{ asset('assets/images/fond2.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-clipboard-check me-1"></i>{{ __('messages.modules.attendance') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide2_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide2_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="2">
        <img src="{{ asset('assets/images/fond3.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-file-earmark-text me-1"></i>{{ __('messages.modules.bulletins') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide3_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide3_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="3">
        <img src="{{ asset('assets/images/fond4.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-cash-coin me-1"></i>{{ __('messages.modules.finances') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide4_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide4_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="4">
        <img src="{{ asset('assets/images/fond5.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-calendar-week me-1"></i>{{ __('messages.modules.timetable') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide5_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide5_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="5">
        <img src="{{ asset('assets/images/fond6.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-people-fill me-1"></i>{{ __('messages.modules.parents') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide6_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide6_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="6">
        <img src="{{ asset('assets/images/fond7.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-pencil-square me-1"></i>{{ __('messages.modules.grades') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide7_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide7_quote') }}</p>
        </div>
    </div>
</div>

{{-- Progress bar --}}
<div class="bg-progress" aria-hidden="true">
    <div class="bg-progress-bar" id="bgProgressBar"></div>
</div>

{{-- Dot indicators --}}
<div class="bg-dots" aria-hidden="true">
    <button class="bg-dot active" data-slide="0"></button>
    <button class="bg-dot" data-slide="1"></button>
    <button class="bg-dot" data-slide="2"></button>
    <button class="bg-dot" data-slide="3"></button>
    <button class="bg-dot" data-slide="4"></button>
    <button class="bg-dot" data-slide="5"></button>
    <button class="bg-dot" data-slide="6"></button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── BACKGROUND CAROUSEL ── */
    const slides   = document.querySelectorAll('.bg-slide');
    const dotBtns  = document.querySelectorAll('.bg-dot');
    const progBar  = document.getElementById('bgProgressBar');
    const DURATION = 6000;
    let current = 0;
    let timer   = null;

    function goTo(idx) {
        slides[current].classList.remove('active');
        dotBtns[current].classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        dotBtns[current].classList.add('active');
        animateProgress();
    }

    function animateProgress() {
        progBar.style.transition = 'none';
        progBar.style.width = '0%';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            progBar.style.transition = `width ${DURATION}ms linear`;
            progBar.style.width = '100%';
        }));
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(() => goTo(current + 1), DURATION);
    }

    dotBtns.forEach((btn, i) => btn.addEventListener('click', () => { goTo(i); startTimer(); }));

    /* Swipe support */
    let touchX = 0;
    document.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    document.addEventListener('touchend', e => {
        const diff = touchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); startTimer(); }
    });

    animateProgress();
    startTimer();
});
</script>
