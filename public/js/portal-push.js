/* POSUCI 360 Conecta — activar/desactivar notificaciones push del navegador.
   Sin build: se sirve estático, usa la Push API nativa del navegador. */
(function () {
    'use strict';

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function postJson(url, csrfToken, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
    }

    function initPush() {
        var btn = document.getElementById('pushToggleBtn');
        if (! btn || ! ('serviceWorker' in navigator) || ! ('PushManager' in window)) {
            return;
        }

        btn.style.display = 'inline-block';

        function setLabel(subscribed) {
            btn.textContent = subscribed ? '🔔 Notificaciones activadas' : '🔔 Activar notificaciones';
        }

        navigator.serviceWorker.register('/sw.js').then(function (registration) {
            registration.pushManager.getSubscription().then(function (sub) {
                setLabel(!! sub);
            });

            btn.addEventListener('click', function () {
                registration.pushManager.getSubscription().then(function (existing) {
                    if (existing) {
                        var endpoint = existing.endpoint;
                        existing.unsubscribe().then(function () {
                            postJson(btn.dataset.unsubscribeUrl, btn.dataset.csrf, { endpoint: endpoint });
                            setLabel(false);
                        });
                        return;
                    }

                    if (Notification.permission === 'denied') {
                        window.alert('Tienes las notificaciones bloqueadas para este sitio en tu navegador. Actívalas desde la configuración del sitio para poder usarlas.');
                        return;
                    }

                    fetch(btn.dataset.keyUrl)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (! data.publicKey) {
                                window.alert('Las notificaciones todavía no están disponibles.');
                                return;
                            }

                            registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(data.publicKey),
                            }).then(function (sub) {
                                postJson(btn.dataset.subscribeUrl, btn.dataset.csrf, sub.toJSON());
                                setLabel(true);
                            }).catch(function () {
                                window.alert('No se pudo activar las notificaciones. Puede que tu navegador las tenga bloqueadas.');
                            });
                        });
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initPush);
})();
