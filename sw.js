self.addEventListener('install', event => {
  event.waitUntil(caches.open('smart-notice-book-v1').then(cache => {
    return cache.addAll(['./', './index.php', './manifest.json']);
  }));
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
