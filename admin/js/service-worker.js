self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: '../images/logo.png',
        badge: '../images/badge.png',
        dir: 'rtl',
        lang: 'ar'
    };

    event.waitUntil(
        self.registration.showNotification('Printly - برنتلي', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/')
    );
});
