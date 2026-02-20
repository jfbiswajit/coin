"use strict";

const CACHE_VERSION = "v1";
const SHELL_CACHE = `coin-shell-${CACHE_VERSION}`;
const API_CACHE = `coin-api-${CACHE_VERSION}`;

const SHELL_ASSETS = ["/offline.html"];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((k) => k !== SHELL_CACHE && k !== API_CACHE)
                    .map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET and cross-origin
    if (request.method !== "GET" || url.origin !== self.location.origin) return;

    const isApi = url.pathname.startsWith("/api/") || url.pathname.startsWith("/transactions") || url.pathname.startsWith("/budget") || url.pathname.startsWith("/categories") || url.pathname.startsWith("/reports") || url.pathname.startsWith("/dashboard");
    const isStatic = /\.(js|css|woff2?|ttf|png|jpg|svg|ico)$/.test(url.pathname);

    if (isStatic) {
        // Cache First for static assets
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    const clone = response.clone();
                    caches.open(SHELL_CACHE).then((c) => c.put(request, clone));
                    return response;
                });
            })
        );
        return;
    }

    if (isApi) {
        // Network First with stale fallback for page/api requests
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(API_CACHE).then((c) => c.put(request, clone));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match("/offline.html"))
                )
        );
        return;
    }

    // Default: network first
    event.respondWith(
        fetch(request).catch(() => caches.match("/offline.html"))
    );
});

// Background Sync for offline transactions
self.addEventListener("sync", (event) => {
    if (event.tag === "sync-transactions") {
        event.waitUntil(syncOfflineTransactions());
    }
});

async function syncOfflineTransactions() {
    const clients = await self.clients.matchAll();
    clients.forEach((client) => client.postMessage({ type: "SYNC_TRANSACTIONS" }));
}
