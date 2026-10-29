/* POSUCI 360 Conecta — service worker mínimo, solo para notificaciones push.
   No cachea nada (sin soporte offline): su único trabajo es mostrar la notificación
   que llega por Web Push y abrir/enfocar el portal cuando la tocan. */

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
    var data = { title: 'POSUCI 360 Conecta', body: 'Tienes algo nuevo en tu portal.', url: '/portal' };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) { /* payload no era JSON válido: se usa el texto por defecto */ }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/icons/posuci-icon.svg',
            badge: '/icons/posuci-icon.svg',
            data: { url: data.url || '/portal' },
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/portal';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
            for (var i = 0; i < clients.length; i++) {
                if (clients[i].url.indexOf(url) !== -1 && 'focus' in clients[i]) {
                    return clients[i].focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
