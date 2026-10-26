<?php
#Service worker shim: phục vụ assets/js/sw.js với header Service-Worker-Allowed
#để service worker nằm trong thư mục con vẫn điều khiển được toàn bộ WebUI (/).
#Code By: Vũ Tuyển
header('Content-Type: application/javascript; charset=utf-8');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache');
readfile(__DIR__ . '/assets/js/sw.js');
