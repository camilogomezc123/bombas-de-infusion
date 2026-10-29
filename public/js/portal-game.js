/* POSUCI 360 Conecta — celebraciones del portal ("modo aventura"). Sin build: se sirve
   estático y usa canvas-confetti por CDN (ver portal/layout.blade.php). */
(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.posuciCelebrate = function () {
        if (prefersReducedMotion || typeof confetti !== 'function') {
            return;
        }

        confetti({
            particleCount: 90,
            spread: 70,
            startVelocity: 35,
            origin: { y: 0.7 },
            colors: ['#7c3aed', '#ec4899', '#facc15', '#22c55e', '#0ea5e9'],
        });
    };

    document.addEventListener('livewire:init', function () {
        if (typeof Livewire === 'undefined') {
            return;
        }

        Livewire.on('celebrate', function () {
            window.posuciCelebrate();
        });
    });

    /* Reacción tipo "me gusta" sobre el propio contenido (diario) — solo decorativa y
       privada de este navegador (localStorage), no es interacción social real: no hay
       nadie más reaccionando, es una forma de darle cariño a lo que uno mismo escribió. */
    function reactionKey(id) {
        return 'posuci-reaction-' + id;
    }

    window.posuciToggleReaction = function (button, id) {
        var active = button.classList.toggle('active');
        try {
            if (active) {
                localStorage.setItem(reactionKey(id), '1');
            } else {
                localStorage.removeItem(reactionKey(id));
            }
        } catch (e) { /* almacenamiento no disponible: la reacción sigue funcionando solo en esta vista */ }
    };

    function restoreReactions() {
        document.querySelectorAll('[data-reaction-id]').forEach(function (button) {
            var id = button.getAttribute('data-reaction-id');
            try {
                if (localStorage.getItem(reactionKey(id)) === '1') {
                    button.classList.add('active');
                }
            } catch (e) { /* almacenamiento no disponible */ }
        });
    }

    document.addEventListener('DOMContentLoaded', restoreReactions);
    document.addEventListener('livewire:navigated', restoreReactions);

    /* "Leer esta página" — usa la Web Speech API nativa del navegador (sin librerías
       externas). Es parte del "modo fácil": lee en voz alta el contenido principal para
       quien prefiera escuchar en vez de leer. Si el navegador no la soporta, el botón
       simplemente se queda oculto (progressive enhancement). */
    function initReadAloud() {
        var btn = document.getElementById('readAloudBtn');
        if (! btn || ! ('speechSynthesis' in window)) {
            return;
        }

        btn.style.display = 'inline-block';
        var speaking = false;

        var reset = function () {
            speaking = false;
            btn.textContent = '🔊 Leer esta página';
        };

        btn.addEventListener('click', function () {
            if (speaking) {
                window.speechSynthesis.cancel();
                reset();
                return;
            }

            var main = document.querySelector('main');
            var text = main ? main.innerText.trim() : '';
            if (! text) {
                return;
            }

            var utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'es-ES';
            utterance.rate = 0.95;
            utterance.onend = reset;
            utterance.onerror = reset;

            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
            speaking = true;
            btn.textContent = '⏹️ Detener lectura';
        });
    }

    document.addEventListener('DOMContentLoaded', initReadAloud);
})();
