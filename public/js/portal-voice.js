/* POSUCI 360 Conecta — "hablar en vez de escribir": botón de micrófono junto a los
   campos de texto largos (diario, solicitudes de ayuda), usando la Web Speech API
   nativa del navegador (sin librerías externas). Si el navegador no la soporta, los
   botones se quedan ocultos por CSS (progressive enhancement, ver body.voice-input-
   supported en portal-game.css). Usa delegación de eventos a nivel de documento para
   que siga funcionando después de que Livewire vuelva a pintar el formulario. */
(function () {
    'use strict';

    var SpeechRecognitionImpl = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (! SpeechRecognitionImpl) {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('voice-input-supported');
    });
    document.addEventListener('livewire:navigated', function () {
        document.body.classList.add('voice-input-supported');
    });

    var activeRecognition = null;
    var activeButton = null;

    function stopActive() {
        if (activeRecognition) {
            activeRecognition.stop();
        }
    }

    function resetButton(btn) {
        btn.classList.remove('is-listening');
        if (activeButton === btn) {
            activeRecognition = null;
            activeButton = null;
        }
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.voice-input-btn');
        if (! btn) {
            return;
        }

        event.preventDefault();

        var target = document.getElementById(btn.dataset.target);
        if (! target) {
            return;
        }

        if (activeButton === btn) {
            stopActive();
            return;
        }

        stopActive();

        var recognition = new SpeechRecognitionImpl();
        recognition.lang = 'es-CO';
        recognition.interimResults = false;
        recognition.continuous = true;

        recognition.onresult = function (e) {
            var transcript = '';
            for (var i = e.resultIndex; i < e.results.length; i++) {
                transcript += e.results[i][0].transcript;
            }
            transcript = transcript.trim();
            if (! transcript) {
                return;
            }
            var separator = target.value && ! /\s$/.test(target.value) ? ' ' : '';
            target.value = target.value + separator + transcript;
            target.dispatchEvent(new Event('input', { bubbles: true }));
        };

        recognition.onerror = function () {
            resetButton(btn);
        };
        recognition.onend = function () {
            resetButton(btn);
        };

        recognition.start();
        btn.classList.add('is-listening');
        activeRecognition = recognition;
        activeButton = btn;
    });
})();
