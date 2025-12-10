/**
 * Cloudflare Worker untuk Domain Masking
 *
 * Setup:
 * 1. Login ke Cloudflare Dashboard
 * 2. Pilih domain Anda
 * 3. Buka Workers & Pages
 * 4. Create a Worker
 * 5. Paste kode ini
 * 6. Deploy
 * 7. Add route: domainanda.com/* -> worker ini
 */

// ========== KONFIGURASI ==========
const TARGET_URL = 'https://website-asli-yang-ingin-disembunyikan.com';

// Whitelist origins (opsional, kosongkan jika ingin semua bisa akses)
const ALLOWED_ORIGINS = [
    // 'https://domainanda.com',
    // 'https://www.domainanda.com'
];

// Enable caching
const ENABLE_CACHE = true;
const CACHE_TTL = 3600; // 1 hour in seconds

// ========== MAIN HANDLER ==========
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    try {
        const url = new URL(request.url);

        // Security: Check whitelist jika diaktifkan
        if (ALLOWED_ORIGINS.length > 0) {
            const origin = request.headers.get('Origin');
            if (origin && !ALLOWED_ORIGINS.includes(origin)) {
                return new Response('Access Denied', { status: 403 });
            }
        }

        // Build target URL
        const targetUrl = TARGET_URL + url.pathname + url.search;

        // Check cache jika diaktifkan
        if (ENABLE_CACHE && request.method === 'GET') {
            const cache = caches.default;
            let response = await cache.match(request);

            if (response) {
                // Return cached response
                return response;
            }
        }

        // Clone headers
        const headers = new Headers(request.headers);

        // Modify headers
        headers.set('X-Forwarded-For', request.headers.get('CF-Connecting-IP') || '');
        headers.set('X-Forwarded-Proto', url.protocol.replace(':', ''));
        headers.set('X-Forwarded-Host', url.hostname);

        // Create modified request
        const modifiedRequest = new Request(targetUrl, {
            method: request.method,
            headers: headers,
            body: request.body,
            redirect: 'follow'
        });

        // Fetch dari target
        let response = await fetch(modifiedRequest);

        // Clone response untuk modifikasi
        response = new Response(response.body, response);

        // Modify response headers
        response.headers.set('Access-Control-Allow-Origin', '*');
        response.headers.set('X-Proxy-By', 'Cloudflare-Worker');
        response.headers.delete('X-Powered-By');

        // Add to cache jika GET request
        if (ENABLE_CACHE && request.method === 'GET' && response.ok) {
            const cacheResponse = response.clone();
            cacheResponse.headers.set('Cache-Control', `public, max-age=${CACHE_TTL}`);

            const cache = caches.default;
            event.waitUntil(cache.put(request, cacheResponse));
        }

        // Rewrite URLs dalam HTML (opsional, untuk mencegah link keluar)
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('text/html')) {
            let html = await response.text();
            html = rewriteUrls(html, TARGET_URL, `${url.protocol}//${url.hostname}`);

            return new Response(html, {
                status: response.status,
                statusText: response.statusText,
                headers: response.headers
            });
        }

        return response;

    } catch (error) {
        return new Response(`Proxy Error: ${error.message}`, {
            status: 502,
            statusText: 'Bad Gateway'
        });
    }
}

/**
 * Rewrite URLs dalam content
 */
function rewriteUrls(content, fromUrl, toUrl) {
    try {
        const fromDomain = new URL(fromUrl);
        const fromBase = `${fromDomain.protocol}//${fromDomain.hostname}`;

        // Replace absolute URLs
        content = content.replace(new RegExp(fromBase, 'g'), toUrl);

        // Replace protocol-relative URLs
        content = content.replace(new RegExp(`//${fromDomain.hostname}`, 'g'), `//${new URL(toUrl).hostname}`);

        return content;
    } catch (error) {
        console.error('URL rewrite error:', error);
        return content;
    }
}

/**
 * Handle CORS preflight
 */
async function handleOptions(request) {
    return new Response(null, {
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
            'Access-Control-Max-Age': '86400',
        }
    });
}

/**
 * INSTRUKSI DEPLOYMENT:
 *
 * 1. Login ke Cloudflare Dashboard (https://dash.cloudflare.com)
 * 2. Pilih domain Anda atau tambah domain baru
 * 3. Klik "Workers & Pages" di sidebar
 * 4. Klik "Create Application"
 * 5. Pilih "Create Worker"
 * 6. Paste kode ini ke editor
 * 7. Ganti TARGET_URL dengan URL yang ingin di-mask
 * 8. Klik "Save and Deploy"
 * 9. Klik "Add route"
 * 10. Masukkan: domainanda.com/* (ganti dengan domain Anda)
 * 11. Pilih worker yang baru dibuat
 * 12. Save
 *
 * FITUR:
 * ✅ Global CDN (sangat cepat)
 * ✅ Built-in caching
 * ✅ SSL otomatis
 * ✅ DDoS protection
 * ✅ Gratis untuk 100,000 requests/hari
 * ✅ URL rewriting otomatis
 *
 * NOTES:
 * - Free tier: 100,000 requests per hari
 * - Paid plan: $5/bulan untuk 10 juta requests
 * - Response time: ~50-100ms (sangat cepat!)
 * - Global edge network
 */
