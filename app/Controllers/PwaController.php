<?php
declare(strict_types=1);

final class PwaController extends Controller
{
    /**
     * GET /manifest.json
     * Returns PWA manifest (baseUrl-safe).
     */
    public function manifest(): void
    {
        $base = rtrim((string)$this->baseUrl(), '/');

        $manifest = [
            'name' => 'Zoro',
            'short_name' => 'Zoro',
            'start_url' => $base . '/dashboard',
            'scope' => $base . '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#111111',
            'icons' => [
                [
                    'src' => $base . '/assets/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => $base . '/assets/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=UTF-8');
        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /service-worker.js
     * Basic SW: cache app shell + offline fallback for same-origin GET requests.
     */
    public function serviceWorker(): void
    {
        $base = rtrim((string)$this->baseUrl(), '/');

        header('Content-Type: application/javascript; charset=UTF-8');

        // IMPORTANT: keep JS minimal and safe for subfolder hosting
        echo <<<JS
/* Zoro Service Worker (basic) */
const CACHE_NAME = 'zoro-cache-v1';
const BASE = '{$base}';

const APP_SHELL = [
  BASE + '/',
  BASE + '/dashboard',
  BASE + '/login'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.map((k) => (k !== CACHE_NAME ? caches.delete(k) : Promise.resolve(true)))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Only handle GET same-origin requests
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // If not same origin, ignore
  if (url.origin !== self.location.origin) return;

  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;

      return fetch(req).then((resp) => {
        // Cache successful basic responses
        const copy = resp.clone();
        caches.open(CACHE_NAME).then((cache) => {
          // Avoid caching very large or opaque responses (basic check)
          if (copy && copy.ok && copy.type === 'basic') {
            cache.put(req, copy);
          }
        });
        return resp;
      }).catch(() => {
        // Offline fallback (try cached dashboard)
        return caches.match(BASE + '/dashboard') || caches.match(BASE + '/');
      });
    })
  );
});
JS;
        exit;
    }
}
