/*
  Service Worker VBot WebUI - PWA
  #Code By: Vũ Tuyển (bổ trợ cho WebUI VBot Assistant)

  File này nằm ở assets/js/ nhưng được phục vụ qua sw.php kèm header
  "Service-Worker-Allowed: /" để có quyền điều khiển toàn bộ WebUI.
  Mọi URL bên trong dùng đường dẫn tuyệt đối từ gốc nên không phụ thuộc vị trí file.

  Chiến lược:
  - Tài nguyên tĩnh (css/js/font/img): stale-while-revalidate — trả bản cache ngay
    lập tức cho trang mở nhanh, đồng thời ngầm tải bản mới cho lần sau.
  - Trang PHP và mọi API động: LUÔN đi qua mạng, không cache (tránh giao diện/
    cấu hình cũ). Mất mạng khi mở trang → hiện trang offline.html.
  - Khi nâng cấp WebUI: tăng VBOT_SW_VERSION bên dưới, trình duyệt sẽ tự xóa
    cache cũ và nạp lại tài nguyên mới.
*/
'use strict';

const VBOT_SW_VERSION = 'v2';
const CACHE_NAME = 'vbot-webui-static-' + VBOT_SW_VERSION;

//Tài nguyên lõi tải sẵn lần đầu cài app (còn lại tự cache khi người dùng dùng tới)
//Font icon được precache: nếu không, lần mở đầu mà font tải lỗi thì toàn bộ icon
//trên thanh header hiển thị thành ô vuông cho tới khi tải lại trang.
const PRECACHE_URLS = [
  '/offline.html',
  '/manifest.json',
  '/assets/img/icon-192.png',
  '/assets/img/icon-512.png',
  '/assets/img/icon-maskable-512.png',
  '/assets/css/style.css',
  '/assets/css/dark_mode.css',
  '/assets/vendor/bootstrap/css/bootstrap.min.css',
  '/assets/vendor/bootstrap-icons/bootstrap-icons.css',
  '/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
  '/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff',
  '/assets/vendor/boxicons/css/boxicons.min.css',
  '/assets/vendor/remixicon/remixicon.css'
];

//Phần mở rộng coi là tĩnh; mọi thứ khác (đặc biệt là .php) luôn lấy từ mạng
const STATIC_EXTENSIONS =
  /\.(css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff|woff2|ttf|eot|otf|map)$/i;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(new Set(PRECACHE_URLS)))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key.startsWith('vbot-webui-static-') && key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

function isStaticAsset(url) {
  if (url.pathname.endsWith('/sw.js') || url.pathname.endsWith('/sw.php')) return false;
  return STATIC_EXTENSIONS.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  let url;
  try {
    url = new URL(request.url);
  } catch (error) {
    return;
  }
  if (url.origin !== self.location.origin) return;

  //Điều hướng trang (mở index.php, Config.php...): mạng là chính, offline thì báo
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() =>
        caches.match('/offline.html').then((cached) =>
          cached || new Response('Không kết nối được tới loa VBot', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' }
          })
        )
      )
    );
    return;
  }

  //Trang PHP / API động: không đụng vào cache
  if (!isStaticAsset(url)) return;

  //Tài nguyên tĩnh: stale-while-revalidate
  //ignoreSearch: khớp cả khi URL có tham số phiên bản (?v=..., ?hash font...)
  event.respondWith(
    caches.match(request, { ignoreSearch: true }).then((cached) => {
      const refresh = fetch(request).then((response) => {
        if (response && response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
      }).catch(() => undefined);
      return cached || refresh || new Response('', { status: 504 });
    })
  );
});
