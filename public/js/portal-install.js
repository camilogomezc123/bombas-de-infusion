/* POSUCI 360 Conecta — botón "Instalar app": aprovecha que ya es una PWA (manifest.json
   + service worker, ver portal-push.js) para agregarla a la pantalla de inicio con un
   toque, en vez de depender de que la persona recuerde una URL y abra el navegador. En
   iOS (que no soporta el evento beforeinstallprompt) se muestran instrucciones en su
   lugar, porque ahí "instalar" es un gesto manual del usuario en Safari. */
(function () {
    'use strict';

    var deferredPrompt = null;

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('installAppBtn');
        if (! btn || isStandalone()) {
            return;
        }

        if (isIos()) {
            btn.textContent = '📲 Cómo instalar';
            btn.style.display = 'inline-block';
            btn.addEventListener('click', function () {
                window.alert('Para instalar POSUCI 360 en tu iPhone/iPad: toca el botón Compartir de Safari (el cuadrito con la flecha hacia arriba) y luego "Agregar a pantalla de inicio".');
            });
            return;
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredPrompt = event;
            btn.style.display = 'inline-block';
        });

        btn.addEventListener('click', function () {
            if (! deferredPrompt) {
                return;
            }
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                btn.style.display = 'none';
            });
        });

        window.addEventListener('appinstalled', function () {
            btn.style.display = 'none';
        });
    });
})();
