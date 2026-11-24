<?php
#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Email: VBot.Assistant@gmail.com

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

include 'Configuration.php';

// Face.php cho phép xem trực tiếp không cần đăng nhập (phục vụ màn hình OLED / Kiosk / máy tính bảng).
// Nếu bật user_login mà chưa đăng nhập, chỉ ẩn nút điều khiển nguồn (Start/Stop/Restart/Reboot).
$userLoginActive = !empty($Config['contact_info']['user_login']['active']);
$isLoggedIn = !$userLoginActive;
if (session_status() === PHP_SESSION_NONE) {
  @session_start();
}
if ($userLoginActive && session_status() === PHP_SESSION_ACTIVE) {
  if (
    isset($_SESSION['user_login']) &&
    (!isset($_SESSION['user_login']['login_time']) || (time() - $_SESSION['user_login']['login_time'] <= 43200))
  ) {
    $isLoggedIn = true;
  }
}

$VBot_Csrf_Token = '';
if (session_status() === PHP_SESSION_ACTIVE) {
  if (
    !isset($_SESSION['vbot_csrf_token'])
    || !is_string($_SESSION['vbot_csrf_token'])
    || strlen($_SESSION['vbot_csrf_token']) < 64
  ) {
    $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
  }
  $VBot_Csrf_Token = $_SESSION['vbot_csrf_token'];
}

$pipelineCfg = (isset($Config['processing_pipeline']) && is_array($Config['processing_pipeline'])) ? $Config['processing_pipeline'] : [];
$faceSseActive = !empty($pipelineCfg['face_display_sse_active']);
$apiPort = (int)($Config['api']['port'] ?? 5002);
$apiAuthActive = !empty($Config['api']['auth']['active']);
$apiKeyParam = ($apiAuthActive && !empty($Config['api']['auth']['api_key'])) ? (string)$Config['api']['auth']['api_key'] : '';
$speakerRoom = (!empty($Config['speaker_location']['active']) && !empty($Config['speaker_location']['room']))
  ? trim((string)$Config['speaker_location']['room'])
  : '';
$weatherLat = trim((string)($Config['contact_info']['location']['latitude'] ?? ''));
$weatherLon = trim((string)($Config['contact_info']['location']['longitude'] ?? ''));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#04060c">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>VBot AI Face Display</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link rel="manifest" href="manifest.json">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --bg: #04060c;
      --accent: #38e8ff;
      --accent-2: #0ea5e9;
      --glow: rgba(56, 232, 255, 0.34);
      --text: #f1f5f9;
      --muted: rgba(203, 213, 225, 0.68);
      --glass: rgba(15, 23, 42, 0.56);
      --glass-border: rgba(148, 163, 184, 0.16);
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100dvh;
      overflow: hidden;
      background: var(--bg);
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", sans-serif;
      user-select: none;
    }

    .screen-root {
      position: relative;
      width: 100%;
      height: 100dvh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: clamp(14px, 2.6vw, 28px);
      background:
        radial-gradient(circle at 50% 48%, var(--glow) 0%, rgba(4, 6, 12, 0.92) 56%, #020308 100%);
      transition: background 0.6s ease;
    }

    /* Subtle OLED scanline texture */
    .screen-root::after {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background: repeating-linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.014) 0px,
        rgba(255, 255, 255, 0.014) 1px,
        transparent 1px,
        transparent 4px
      );
      opacity: 0.45;
    }

    /* TOP HUD */
    .top-hud {
      width: 100%;
      max-width: 1120px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      z-index: 10;
      transition: opacity 0.5s ease;
    }
    .clock-block {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .brand-bar {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 2px;
    }
    .brand-logo {
      width: clamp(22px, 2.8vw, 30px);
      height: clamp(22px, 2.8vw, 30px);
      border-radius: 8px;
      object-fit: contain;
      filter: drop-shadow(0 0 8px var(--glow));
    }
    .brand-name {
      font-size: clamp(13px, 1.8vw, 17px);
      font-weight: 700;
      letter-spacing: 0.06em;
      background: linear-gradient(90deg, #ffffff 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .clock-time {
      font-size: clamp(28px, 5vw, 52px);
      font-weight: 700;
      letter-spacing: 0.04em;
      line-height: 1;
      text-shadow: 0 0 24px var(--glow);
      font-variant-numeric: tabular-nums;
    }
    .clock-date {
      font-size: clamp(12px, 1.5vw, 15px);
      color: var(--muted);
      letter-spacing: 0.03em;
    }

    .hud-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: flex-end;
      transition: opacity 0.4s ease;
    }
    .hud-controls.dimmed {
      opacity: 0.22;
    }
    .hud-controls:hover {
      opacity: 1 !important;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 12px;
      border-radius: 999px;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(12px);
      font-size: 12px;
      color: var(--muted);
    }
    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #64748b;
      box-shadow: 0 0 8px currentColor;
    }
    .status-dot.online { background: #22c55e; color: #22c55e; }
    .status-dot.warn { background: #f59e0b; color: #f59e0b; }
    .status-dot.off { background: #ef4444; color: #ef4444; }

    .hud-btn {
      border: 1px solid var(--glass-border);
      background: var(--glass);
      color: var(--text);
      border-radius: 999px;
      padding: 6px 12px;
      font-size: 12px;
      cursor: pointer;
      text-decoration: none;
      backdrop-filter: blur(12px);
      transition: background 0.2s, border-color 0.2s, transform 0.15s;
    }
    .hud-btn:hover {
      background: rgba(56, 232, 255, 0.16);
      border-color: var(--accent);
      transform: translateY(-1px);
    }

    /* POWER CONTROL DROPDOWN */
    .power-dropdown {
      position: relative;
      display: inline-flex;
      align-items: center;
    }
    .power-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      color: #f87171;
      border-color: rgba(248, 113, 113, 0.34);
    }
    .power-btn:hover,
    .power-dropdown.open .power-btn {
      background: rgba(239, 68, 68, 0.22);
      border-color: #ef4444;
      color: #fecaca;
      box-shadow: 0 0 14px rgba(239, 68, 68, 0.32);
    }
    .power-menu {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      min-width: 196px;
      padding: 6px;
      margin: 0;
      list-style: none;
      border-radius: 16px;
      background: rgba(9, 13, 24, 0.96);
      border: 1px solid rgba(148, 163, 184, 0.24);
      box-shadow: 0 18px 46px rgba(0, 0, 0, 0.72), 0 0 24px rgba(56, 232, 255, 0.12);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      z-index: 200;
      opacity: 0;
      transform: translateY(-6px) scale(0.97);
      pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .power-dropdown.open .power-menu {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
    }
    .power-menu-item {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border: 0;
      border-radius: 10px;
      background: transparent;
      color: #e2e8f0;
      font-size: 13px;
      font-weight: 500;
      text-align: left;
      cursor: pointer;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .power-menu-item:hover {
      background: rgba(56, 232, 255, 0.14);
      color: #ffffff;
    }
    .power-menu-item.danger-item {
      color: #fca5a5;
    }
    .power-menu-item.danger-item:hover {
      background: rgba(239, 68, 68, 0.22);
      color: #ffffff;
    }
    .power-menu-item i {
      font-size: 15px;
      width: 18px;
      text-align: center;
      flex-shrink: 0;
    }
    .power-divider {
      height: 1px;
      margin: 4px 8px;
      background: rgba(148, 163, 184, 0.16);
    }

    /* FACE ACTION TOAST */
    .face-toast {
      position: fixed;
      top: 18px;
      left: 50%;
      transform: translateX(-50%) translateY(-18px);
      z-index: 10000;
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 9px 18px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.94);
      border: 1px solid rgba(56, 232, 255, 0.42);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(14px);
      color: #f8fafc;
      font-size: 13px;
      font-weight: 500;
      max-width: min(92vw, 520px);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .face-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }
    .face-toast.ok { border-color: rgba(34, 197, 94, 0.58); color: #dcfce7; }
    .face-toast.err { border-color: rgba(239, 68, 68, 0.62); color: #fee2e2; }
    .face-toast.info { border-color: rgba(56, 232, 255, 0.52); color: #e0f2fe; }

    /* CENTER FACE STAGE */
    .face-stage {
      position: relative;
      flex: 1;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 5;
      min-height: 0;
    }

    .face-wrap {
      position: relative;
      width: min(76vw, 540px);
      max-height: 48dvh;
      aspect-ratio: 16 / 9;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .face-svg {
      width: 100%;
      height: 100%;
      overflow: visible;
      filter: drop-shadow(0 0 22px var(--glow));
      transition: filter 0.45s ease;
    }

    .eye-group {
      transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
      transform-origin: center;
    }

    .face-wrap.blinking .eye-left,
    .face-wrap.blinking .eye-right {
      transform: scaleY(0.08);
    }

    .emotion-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 10px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.52);
      border: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 12px;
      letter-spacing: 0.03em;
      color: var(--muted);
      backdrop-filter: blur(10px);
    }
    .emotion-badge code {
      color: var(--accent);
      font-weight: 600;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    /* NOW PLAYING PILL + ALBUM COVER + MUSIC EQUALIZER */
    .media-pill {
      margin-top: 14px;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 8px 18px 8px 10px;
      border-radius: 999px;
      background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(56, 232, 255, 0.08));
      border: 1px solid rgba(56, 232, 255, 0.34);
      box-shadow: 0 0 24px rgba(56, 232, 255, 0.18);
      backdrop-filter: blur(12px);
      font-size: 13px;
      font-weight: 500;
      color: #e0f2fe;
      max-width: min(90vw, 560px);
      opacity: 0;
      transform: translateY(10px) scale(0.95);
      pointer-events: none;
      transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .media-pill.visible {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
    }
    .media-pill.no-cover {
      padding-left: 16px;
    }
    .media-cover-wrap {
      position: relative;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      flex-shrink: 0;
      padding: 2px;
      background: linear-gradient(135deg, rgba(56, 232, 255, 0.75), rgba(14, 165, 233, 0.25));
      box-shadow: 0 0 14px rgba(56, 232, 255, 0.35);
      display: none;
    }
    .media-cover-wrap.has-cover {
      display: block;
    }
    .media-cover {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      display: block;
      animation: mediaVinylSpin 14s linear infinite;
    }
    .media-cover-wrap::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #060a14;
      border: 1.5px solid rgba(56, 232, 255, 0.7);
      transform: translate(-50%, -50%);
      pointer-events: none;
    }
    @keyframes mediaVinylSpin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .media-pill-info {
      display: flex;
      flex-direction: column;
      min-width: 0;
      gap: 2px;
      text-align: left;
    }
    .media-pill-meta {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 10px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #7dd3fc;
      font-weight: 700;
    }
    .media-pill-title {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: 13.5px;
      font-weight: 600;
      color: #f0f9ff;
    }
    @media (max-width: 600px) {
      .media-cover-wrap { width: 38px; height: 38px; }
      .media-pill-title { font-size: 12.5px; }
    }
    .media-pill.is-paused .media-cover,
    .media-pill.is-paused .music-eq span {
      animation-play-state: paused !important;
    }
    .media-controls {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-left: 6px;
      flex-shrink: 0;
    }
    .media-ctrl-btn {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 1px solid rgba(56, 232, 255, 0.28);
      background: rgba(15, 23, 42, 0.55);
      color: #e0f2fe;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 13px;
      padding: 0;
      transition: background 0.2s ease, transform 0.15s ease, border-color 0.2s ease;
    }
    .media-ctrl-btn:hover {
      background: rgba(56, 232, 255, 0.22);
      border-color: rgba(56, 232, 255, 0.65);
    }
    .media-ctrl-btn:active {
      transform: scale(0.9);
    }
    .media-ctrl-btn.primary-ctrl {
      width: 34px;
      height: 34px;
      background: rgba(56, 232, 255, 0.22);
      border-color: rgba(56, 232, 255, 0.55);
      font-size: 15px;
    }
    .media-ctrl-btn.stop-ctrl {
      border-color: rgba(248, 113, 113, 0.38);
      color: #fca5a5;
    }
    .media-ctrl-btn.stop-ctrl:hover {
      background: rgba(239, 68, 68, 0.26);
      border-color: rgba(248, 113, 113, 0.75);
      color: #fee2e2;
    }

    /* WEATHER & ACTIVE TIMER BADGES UNDER CLOCK */
    .clock-extras {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-top: 6px;
    }
    .weather-pill {
      display: none;
      align-items: center;
      gap: 6px;
      padding: 3px 10px 3px 6px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid rgba(56, 232, 255, 0.22);
      backdrop-filter: blur(8px);
      font-size: 12px;
      color: #cbd5e1;
      line-height: 1.2;
    }
    .weather-pill.visible {
      display: inline-flex;
    }
    .weather-pill img {
      width: 24px;
      height: 24px;
      object-fit: contain;
      margin: -3px 0;
    }
    .weather-temp {
      font-weight: 700;
      color: #f8fafc;
    }
    .weather-desc {
      color: #94a3b8;
      text-transform: capitalize;
    }
    .timer-badges {
      display: inline-flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }
    .timer-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 999px;
      background: rgba(245, 158, 11, 0.16);
      border: 1px solid rgba(251, 191, 36, 0.42);
      color: #fde68a;
      font-size: 12px;
      font-weight: 600;
      font-variant-numeric: tabular-nums;
      backdrop-filter: blur(8px);
    }
    .timer-badge.sleep-timer {
      background: rgba(99, 102, 241, 0.18);
      border-color: rgba(129, 140, 248, 0.45);
      color: #c7d2fe;
    }

    /* NIGHT DIM OVERLAY + OLED PIXEL SHIFT */
    .night-dim-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 4, 10, 0.58);
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.6s ease;
      z-index: 9000;
    }
    body.night-dim-active .night-dim-overlay {
      opacity: 1;
    }
    .hud-btn.night-active {
      border-color: rgba(129, 140, 248, 0.65);
      color: #c7d2fe;
      background: rgba(79, 70, 229, 0.22);
    }
    .face-stage {
      transition: transform 1.2s ease;
    }
    .music-eq {
      display: inline-flex;
      align-items: flex-end;
      gap: 3px;
      height: 18px;
      flex-shrink: 0;
    }
    .music-eq span {
      width: 3px;
      height: 4px;
      border-radius: 999px;
      background: var(--accent);
      box-shadow: 0 0 8px var(--accent);
      animation: musicEqBar 0.9s ease-in-out infinite;
    }
    .music-eq span:nth-child(1) { animation-duration: 0.72s; animation-delay: 0.0s; }
    .music-eq span:nth-child(2) { animation-duration: 0.95s; animation-delay: 0.15s; }
    .music-eq span:nth-child(3) { animation-duration: 0.65s; animation-delay: 0.30s; }
    .music-eq span:nth-child(4) { animation-duration: 0.85s; animation-delay: 0.12s; }
    .music-eq span:nth-child(5) { animation-duration: 0.78s; animation-delay: 0.25s; }
    @keyframes musicEqBar {
      0%, 100% { height: 4px; opacity: 0.6; }
      50%      { height: 18px; opacity: 1; }
    }

    /* LISTENING WAVEFORM */
    .waveform {
      display: none;
      align-items: center;
      justify-content: center;
      gap: 6px;
      height: 36px;
      margin-top: 10px;
    }
    .waveform.active { display: flex; }
    .waveform span {
      width: 5px;
      height: 10px;
      border-radius: 999px;
      background: var(--accent);
      box-shadow: 0 0 12px var(--accent);
      animation: wavePulse 0.9s ease-in-out infinite;
    }
    .waveform span:nth-child(2) { animation-delay: 0.12s; }
    .waveform span:nth-child(3) { animation-delay: 0.24s; }
    .waveform span:nth-child(4) { animation-delay: 0.36s; }
    .waveform span:nth-child(5) { animation-delay: 0.24s; }
    .waveform span:nth-child(6) { animation-delay: 0.12s; }
    .waveform span:nth-child(7) { animation-delay: 0.0s; }

    /* BOTTOM SUBTITLE STAGE */
    .bottom-stage {
      width: 100%;
      max-width: 860px;
      min-height: 76px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      gap: 6px;
      z-index: 10;
    }

    .subtitle-box {
      width: 100%;
      max-height: clamp(92px, 26vh, 210px);
      overflow-y: auto;
      overscroll-behavior: contain;
      padding: 12px 20px;
      border-radius: 20px;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(14px);
      text-align: center;
      word-break: break-word;
      scrollbar-width: thin;
      scrollbar-color: rgba(56, 232, 255, 0.35) transparent;
      transition: opacity 0.5s ease, transform 0.4s ease;
    }
    .subtitle-box::-webkit-scrollbar {
      width: 5px;
    }
    .subtitle-box::-webkit-scrollbar-thumb {
      background: rgba(56, 232, 255, 0.35);
      border-radius: 999px;
    }
    .subtitle-box.hidden-soft {
      opacity: 0;
      transform: translateY(8px);
      pointer-events: none;
    }
    .sub-human {
      font-size: clamp(12px, 1.6vw, 14px);
      color: var(--muted);
      margin-bottom: 4px;
    }
    .sub-bot {
      font-size: clamp(15px, 2.3vw, 21px);
      font-weight: 500;
      color: #ffffff;
      line-height: 1.38;
      white-space: pre-wrap;
    }
    .sub-bot.long-text {
      font-size: clamp(13px, 1.8vw, 17px);
      line-height: 1.45;
      text-align: left;
    }

    .sse-off-banner {
      padding: 8px 16px;
      border-radius: 12px;
      background: rgba(245, 158, 11, 0.14);
      border: 1px solid rgba(245, 158, 11, 0.35);
      color: #fde68a;
      font-size: 13px;
      text-align: center;
    }
    .sse-off-banner a { color: #38e8ff; text-decoration: underline; }

    /* SSE DISCONNECT BANNER */
    .sse-disconnect-banner {
      position: fixed !important;
      top: auto !important;
      bottom: 20px !important;
      bottom: clamp(16px, 2.8vw, 24px) !important;
      left: 50% !important;
      transform: translateX(-50%) translateY(0) !important;
      z-index: 9999 !important;
      max-width: min(92vw, 560px) !important;
      pointer-events: none;
      transition: opacity 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                  transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .sse-disconnect-banner.hidden {
      display: none !important;
      opacity: 0 !important;
      transform: translateX(-50%) translateY(28px) !important;
      pointer-events: none !important;
    }
    .sse-disconnect-banner .banner-content {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 9px 20px;
      border-radius: 999px;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.92), rgba(153, 27, 27, 0.94));
      border: 1px solid rgba(254, 202, 202, 0.5);
      box-shadow: 0 0 28px rgba(239, 68, 68, 0.48), 0 8px 24px rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      color: #ffffff;
      cursor: pointer;
      pointer-events: auto;
      user-select: none;
      animation: bannerPulse 2.2s ease-in-out infinite;
      transition: transform 0.15s ease, background 0.2s ease;
    }
    .sse-disconnect-banner .banner-content:hover {
      transform: scale(1.02);
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.96), rgba(185, 28, 28, 0.96));
    }
    .sse-disconnect-banner .banner-icon {
      font-size: clamp(15px, 2vw, 18px);
      color: #fee2e2;
      display: inline-flex;
      align-items: center;
      animation: alertShake 3s ease-in-out infinite;
    }
    .sse-disconnect-banner .banner-text {
      font-size: clamp(13px, 1.8vw, 15px);
      font-weight: 700;
      letter-spacing: 0.03em;
      text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
    }
    .sse-disconnect-banner .banner-sub {
      font-size: clamp(11px, 1.4vw, 12.5px);
      font-weight: 400;
      color: #fecaca;
      opacity: 0.9;
    }
    @keyframes bannerPulse {
      0%, 100% {
        box-shadow: 0 0 22px rgba(239, 68, 68, 0.42), 0 8px 20px rgba(0, 0, 0, 0.6);
        border-color: rgba(254, 202, 202, 0.45);
      }
      50% {
        box-shadow: 0 0 34px rgba(239, 68, 68, 0.72), 0 8px 26px rgba(0, 0, 0, 0.75);
        border-color: rgba(254, 202, 202, 0.85);
      }
    }
    @keyframes alertShake {
      0%, 85%, 100% { transform: rotate(0deg); }
      88%           { transform: rotate(-8deg); }
      92%           { transform: rotate(8deg); }
      96%           { transform: rotate(-5deg); }
    }
    @media (max-width: 768px) {
      .sse-disconnect-banner {
        top: auto !important;
        bottom: 14px !important;
        width: calc(100% - 32px) !important;
      }
      .sse-disconnect-banner .banner-content {
        width: 100%;
        padding: 8px 14px;
        gap: 8px;
        flex-wrap: wrap;
      }
    }

    /* DEMO DRAWER */
    .demo-drawer {
      position: fixed;
      left: 50%;
      bottom: 18px;
      transform: translateX(-50%) translateY(130%);
      width: min(94vw, 760px);
      padding: 14px;
      border-radius: 20px;
      background: rgba(9, 13, 24, 0.94);
      border: 1px solid rgba(56, 232, 255, 0.26);
      backdrop-filter: blur(18px);
      z-index: 120;
      transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
    }
    .demo-drawer.open { transform: translateX(-50%) translateY(0); }
    .demo-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      font-size: 13px;
      color: var(--muted);
    }
    .demo-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      justify-content: center;
    }
    .demo-chip {
      border: 1px solid rgba(148, 163, 184, 0.22);
      background: rgba(30, 41, 59, 0.7);
      color: #e2e8f0;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.15s;
    }
    .demo-chip:hover, .demo-chip.active {
      border-color: var(--accent);
      background: rgba(56, 232, 255, 0.22);
      color: #ffffff;
    }

    /* KEYFRAMES */
    @keyframes wavePulse {
      0%, 100% { height: 8px; opacity: 0.55; }
      50% { height: 32px; opacity: 1; }
    }
    @keyframes floatBounce {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-7px) scale(1.012); }
    }
    @keyframes happyBounce {
      0%, 100% { transform: translateY(0) scale(1); }
      40% { transform: translateY(-10px) scale(1.03); }
      70% { transform: translateY(-3px) scale(1.01); }
    }
    @keyframes laughShake {
      0%, 100% { transform: translateY(0) scale(1); }
      25% { transform: translateY(-8px) scale(1.04) rotate(-1.5deg); }
      75% { transform: translateY(3px) scale(0.98) rotate(1.5deg); }
    }
    @keyframes heartPulse {
      0%, 100% { transform: scale(1); }
      35% { transform: scale(1.13); }
      60% { transform: scale(1.04); }
      80% { transform: scale(1.1); }
    }
    @keyframes loveSway {
      0%, 100% { transform: rotate(-2deg) translateY(0); }
      50% { transform: rotate(2deg) translateY(-6px); }
    }
    @keyframes sadSigh {
      0%, 100% { transform: translateY(0) scaleY(1); }
      50% { transform: translateY(7px) scaleY(0.97); }
    }
    @keyframes angryTremble {
      0%, 100% { transform: translate(0, 0) scale(1.02); }
      20% { transform: translate(-2px, 1px) scale(1.03); }
      40% { transform: translate(2px, -1px) scale(1.02); }
      60% { transform: translate(-1.5px, -1px) scale(1.03); }
      80% { transform: translate(1.5px, 1px) scale(1.02); }
    }
    @keyframes thinkTilt {
      0%, 100% { transform: rotate(-3deg) translateY(0); }
      50% { transform: rotate(3deg) translateY(-5px); }
    }
    @keyframes sleepyNod {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      55% { transform: translateY(9px) rotate(2.5deg); }
      75% { transform: translateY(2px) rotate(-0.5deg); }
    }
    @keyframes listenPulse {
      0%, 100% { transform: scale(1) translateY(0); }
      50% { transform: scale(1.035) translateY(-4px); }
    }
    @keyframes shyWiggle {
      0%, 100% { transform: rotate(0deg) translateX(0); }
      25% { transform: rotate(-2.5deg) translateX(-4px); }
      75% { transform: rotate(2.5deg) translateX(4px); }
    }
    @keyframes coolNod {
      0%, 100% { transform: translateY(0) rotate(-1deg); }
      50% { transform: translateY(-5px) rotate(1.5deg); }
    }
    @keyframes relaxBreathe {
      0%, 100% { transform: scale(0.985) translateY(2px); opacity: 0.92; }
      50% { transform: scale(1.025) translateY(-5px); opacity: 1; }
    }
    @keyframes zzzFloat {
      0% { transform: translate(0, 6px) scale(0.8); opacity: 0; }
      35% { opacity: 0.95; }
      100% { transform: translate(16px, -24px) scale(1.15); opacity: 0; }
    }
    @keyframes tearDrop {
      0% { transform: translateY(0); opacity: 0.95; }
      100% { transform: translateY(26px); opacity: 0; }
    }
    @keyframes thinkDot {
      0%, 80%, 100% { opacity: 0.25; transform: translateY(0); }
      40% { opacity: 1; transform: translateY(-6px); }
    }
    @keyframes shockJitter {
      0%, 100% { transform: translate(0, 0) scale(1.04); }
      25% { transform: translate(-2.5px, 1.5px) scale(1.05); }
      50% { transform: translate(2.5px, -1.5px) scale(1.03); }
      75% { transform: translate(-1.5px, -2px) scale(1.05); }
    }
    @keyframes facePopIn {
      0%   { opacity: 0; transform: scale(0.82); }
      65%  { opacity: 1; transform: scale(1.05); }
      100% { opacity: 1; transform: scale(1); }
    }

    #eyeGaze {
      transition: transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
      transform-origin: center;
    }
    .eye-pupil {
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
    }
    #faceCanvas {
      transform-box: fill-box;
      transform-origin: center;
      transition: opacity 0.14s ease, transform 0.18s ease;
    }
    #faceCanvas.morph-out {
      opacity: 0;
      transform: scale(0.86);
    }
    #faceCanvas.morph-in {
      animation: facePopIn 0.34s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .anim-float  { animation: floatBounce 3.6s ease-in-out infinite; }
    .anim-happy  { animation: happyBounce 1.8s ease-in-out infinite; }
    .anim-laugh  { animation: laughShake 0.45s ease-in-out infinite; }
    .anim-love   { animation: loveSway 2.4s ease-in-out infinite; }
    .anim-sad    { animation: sadSigh 3.2s ease-in-out infinite; }
    .anim-angry  { animation: angryTremble 0.38s ease-in-out infinite; }
    .anim-think  { animation: thinkTilt 2.8s ease-in-out infinite; }
    .anim-sleepy { animation: sleepyNod 4.2s ease-in-out infinite; }
    .anim-listen { animation: listenPulse 1.5s ease-in-out infinite; }
    .anim-shy    { animation: shyWiggle 2.2s ease-in-out infinite; }
    .anim-cool   { animation: coolNod 2.6s ease-in-out infinite; }
    .anim-relax  { animation: relaxBreathe 4.4s ease-in-out infinite; }
    .anim-heart  { animation: heartPulse 1.1s ease-in-out infinite; transform-origin: center; }
    .anim-shock  { animation: shockJitter 0.24s linear infinite; }
    .anim-zzz-1  { animation: zzzFloat 2.6s ease-in-out infinite; }
    .anim-zzz-2  { animation: zzzFloat 2.6s ease-in-out 0.9s infinite; }
    .anim-tear   { animation: tearDrop 1.25s linear infinite; }
    .anim-dot-1  { animation: thinkDot 1.2s ease-in-out infinite; }
    .anim-dot-2  { animation: thinkDot 1.2s ease-in-out 0.2s infinite; }
    .anim-dot-3  { animation: thinkDot 1.2s ease-in-out 0.4s infinite; }

    /* SPEAKING MOUTH ANIMATION (TTS ACTIVE) */
    @keyframes talkMouthOuter {
      0%, 100% { transform: scaleY(0.32) scaleX(0.92); }
      20%      { transform: scaleY(1.08) scaleX(1.04); }
      42%      { transform: scaleY(0.48) scaleX(0.96); }
      65%      { transform: scaleY(1.18) scaleX(1.06); }
      82%      { transform: scaleY(0.55) scaleX(0.98); }
    }
    @keyframes talkPulseFace {
      0%, 100% { transform: scale(1); }
      50%      { transform: scale(1.022); }
    }
    .speaking-mouth-group {
      display: none;
      transform-box: fill-box;
      transform-origin: center;
    }
    .face-wrap.is-speaking {
      animation: talkPulseFace 0.48s ease-in-out infinite !important;
    }
    .face-wrap.is-speaking .speaking-mouth-group {
      display: block;
      animation: talkMouthOuter 0.38s ease-in-out infinite;
    }
    .face-wrap.is-speaking .static-mouth {
      opacity: 0;
    }
    .speaking-pill {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: var(--accent);
      letter-spacing: 0.04em;
    }
    .speaking-pill.active { display: inline-flex; }
  </style>
</head>
<body>
  <div class="screen-root" id="screenRoot" ondblclick="toggleFullscreen()">
    <header class="top-hud">
      <div class="clock-block">
        <div class="brand-bar">
          <img src="assets/img/logo.png" alt="VBot Assistant" class="brand-logo">
          <span class="brand-name">VBot Assistant</span>
          <div class="emotion-badge">
            <span id="emotionEmoji">😶</span>
            <code id="emotionCode">neutral</code>
            <span class="speaking-pill" id="speakingPill"><i class="bi bi-volume-up-fill"></i> đang nói</span>
          </div>
        </div>
        <div class="clock-time" id="clockTime">--:--</div>
        <div class="clock-date" id="clockDate"><?php echo $speakerRoom ? htmlspecialchars($speakerRoom) . ' • ' : ''; ?><span id="clockDateText"></span></div>
        <div class="clock-extras">
          <div class="weather-pill" id="weatherPill" title="Thời tiết hiện tại (nhấn để làm mới)" onclick="loadFaceWeather()" style="cursor:pointer;">
            <img id="faceWeatherIcon" src="" alt="" />
            <span class="weather-temp" id="faceWeatherTemp">--°C</span>
            <span class="weather-desc" id="faceWeatherDesc"></span>
          </div>
          <div class="timer-badges" id="timerBadges"></div>
        </div>
      </div>
      <div class="hud-controls" id="hudControls">
        <div class="status-pill" id="sseStatusPill">
          <span class="status-dot <?php echo $faceSseActive ? 'warn' : 'off'; ?>" id="sseStatusDot"></span>
          <span id="sseStatusText"><?php echo $faceSseActive ? 'Đang kết nối SSE...' : 'SSE đang tắt'; ?></span>
        </div>
        <button type="button" class="hud-btn" id="nightModeBtn" onclick="cycleNightMode()" title="Chế độ ngủ đêm giảm độ sáng (Tự động 22:30–06:00 / Bật / Tắt)"><i class="bi bi-moon-stars"></i> <span id="nightModeLabel">Đêm: Tự động</span></button>
        <button type="button" class="hud-btn" onclick="toggleDemoDrawer()"><i class="bi bi-emoji-smile"></i> 21 Biểu Cảm</button>
        <button type="button" class="hud-btn" onclick="toggleFullscreen()"><i class="bi bi-arrows-fullscreen"></i> Toàn Màn Hình</button>
        <a href="index.php" class="hud-btn"><i class="bi bi-house-door"></i> WebUI</a>
        <?php if ($isLoggedIn): ?>
        <div class="power-dropdown" id="powerDropdown" ondblclick="event.stopPropagation()">
          <button type="button" class="hud-btn power-btn" id="powerMenuBtn" onclick="togglePowerMenu(event)" aria-haspopup="true" aria-expanded="false" title="Lựa chọn hành động">
            <i class="bi bi-power"></i>
          </button>
          <ul class="power-menu" id="powerMenu" role="menu">
            <li role="none">
              <button type="button" class="power-menu-item" role="menuitem" onclick="power_action_service('start_vbot_service','Khởi chạy chương trình VBot')">
                <i class="bi bi-align-start" style="color:#22c55e;"></i>
                <span>Start VBot</span>
              </button>
            </li>
            <li role="none"><div class="power-divider"></div></li>
            <li role="none">
              <button type="button" class="power-menu-item" role="menuitem" onclick="power_action_service('stop_vbot_service','Bạn có chắc chắn muốn dừng chương trình VBot')">
                <i class="bi bi-stop-btn" style="color:#ef4444;"></i>
                <span>Stop VBot</span>
              </button>
            </li>
            <li role="none"><div class="power-divider"></div></li>
            <li role="none">
              <button type="button" class="power-menu-item" role="menuitem" onclick="power_action_service('restart_vbot_service','Bạn có chắc chắn muốn khởi động lại chương trình VBot')">
                <i class="bi bi-arrow-repeat" style="color:#f59e0b;" title="Khởi động lại chương trình VBot"></i>
                <span>Restart VBot</span>
              </button>
            </li>
            <li role="none"><div class="power-divider"></div></li>
            <li role="none">
              <button type="button" class="power-menu-item danger-item" role="menuitem" onclick="power_action_service('reboot_os','Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống')">
                <i class="bi bi-bootstrap-reboot" style="color:#38bdf8;"></i>
                <span>Reboot OS</span>
              </button>
            </li>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </header>

    <main class="face-stage">
      <div class="face-wrap anim-float" id="faceWrap">
        <svg class="face-svg" id="faceSvg" viewBox="0 0 400 220" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="eyeGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" id="gradStop1" stop-color="#7ef9ff"/>
              <stop offset="100%" id="gradStop2" stop-color="#0ea5e9"/>
            </linearGradient>
          </defs>
          <g id="eyeGaze">
            <g id="faceCanvas"></g>
          </g>
          <g class="speaking-mouth-group" id="speakingMouthGroup">
            <rect x="172" y="152" width="56" height="24" rx="12" fill="url(#eyeGrad)"/>
            <rect x="184" y="159" width="32" height="10" rx="5" fill="#04060c" opacity="0.55"/>
          </g>
        </svg>
      </div>

      <div class="waveform" id="listeningWave">
        <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
      </div>

      <div class="media-pill no-cover" id="mediaPill">
        <div class="media-cover-wrap" id="mediaCoverWrap" <?php if ($isLoggedIn): ?>onclick="faceTogglePlayPause()" style="cursor:pointer;" title="Nhấn để Tạm dừng / Tiếp tục"<?php endif; ?>>
          <img class="media-cover" id="mediaCover" src="" alt="" referrerpolicy="no-referrer" />
        </div>
        <div class="media-pill-info">
          <div class="media-pill-meta">
            <div class="music-eq" aria-hidden="true">
              <span></span><span></span><span></span><span></span><span></span>
            </div>
            <span id="mediaStatusLabel">Đang phát</span>
          </div>
          <span class="media-pill-title" id="mediaTitle"></span>
        </div>
        <?php if ($isLoggedIn): ?>
        <div class="media-controls" onclick="event.stopPropagation()">
          <button type="button" class="media-ctrl-btn" onclick="faceControlMedia('previous')" title="Bài trước"><i class="bi bi-skip-start-fill"></i></button>
          <button type="button" class="media-ctrl-btn primary-ctrl" id="mediaPlayPauseBtn" onclick="faceTogglePlayPause()" title="Tạm dừng / Phát tiếp"><i class="bi bi-pause-fill" id="mediaPlayPauseIcon"></i></button>
          <button type="button" class="media-ctrl-btn" onclick="faceControlMedia('next')" title="Bài tiếp theo"><i class="bi bi-skip-end-fill"></i></button>
          <button type="button" class="media-ctrl-btn stop-ctrl" onclick="faceControlMedia('stop')" title="Dừng phát nhạc"><i class="bi bi-stop-fill"></i></button>
        </div>
        <?php endif; ?>
      </div>
    </main>
    <div class="night-dim-overlay" id="nightDimOverlay" aria-hidden="true"></div>

    <footer class="bottom-stage">
      <?php if (!$faceSseActive): ?>
        <div class="sse-off-banner">
          Luồng SSE màn hình biểu cảm đang <b>TẮT</b> trong <code>Config.json</code> (Python không khởi tạo SSE).
          Bật <b>Màn Hình Biểu Cảm AI (SSE)</b> tại <a href="Config.php">Config.php</a> rồi khởi động lại VBot — hoặc bấm <b>21 Biểu Cảm</b> để xem trước!
        </div>
      <?php endif; ?>
      <div class="subtitle-box hidden-soft" id="subtitleBox">
        <div class="sub-human" id="subHuman"></div>
        <div class="sub-bot" id="subBot"></div>
      </div>
      <div class="sse-disconnect-banner hidden" id="sseAlertBanner" role="alert" style="position: fixed !important; top: auto !important; bottom: 20px !important; left: 50% !important; transform: translateX(-50%) !important; z-index: 9999 !important;">
        <div class="banner-content" onclick="connectFaceSse()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();connectFaceSse();}" title="Bấm để thử kết nối lại ngay" role="button" tabindex="0">
          <i class="bi bi-exclamation-triangle banner-icon" aria-hidden="true"></i>
          <span class="banner-text">Mất kết nối tới máy chủ VBot</span>
          <span class="banner-sub">(Đang thử kết nối lại...)</span>
        </div>
      </div>
    </footer>
  </div>

  <div class="face-toast" id="faceToast" role="status" aria-live="polite"></div>

  <div class="demo-drawer" id="demoDrawer">
    <div class="demo-header">
      <span>Xem thử 21 mã cảm xúc trạng thái hệ thống</span>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button type="button" class="hud-btn" id="btnDemoSpeak" onclick="toggleDemoSpeaking()"><i class="bi bi-volume-up"></i> Thử Nói</button>
        <button type="button" class="hud-btn" id="btnDemoDisconnect" onclick="toggleDemoDisconnect()"><i class="bi bi-wifi-off"></i> Thử Mất Kết Nối</button>
        <button type="button" class="hud-btn" onclick="toggleDemoDrawer()">Đóng</button>
      </div>
    </div>
    <div class="demo-grid" id="demoGrid"></div>
  </div>

  <script>
    var VBOT_FACE_SSE_ACTIVE = <?php echo $faceSseActive ? 'true' : 'false'; ?>;
    var VBOT_API_PORT = <?php echo (int)$apiPort; ?>;
    var VBOT_API_KEY = <?php echo json_encode($apiKeyParam); ?>;
    window.VBOT_CSRF_TOKEN = <?php echo json_encode($VBot_Csrf_Token); ?>;

    var EMOTIONS = {
      neutral:     { emoji: '😶', c1: '#7ef9ff', c2: '#0ea5e9', glow: 'rgba(56, 232, 255, 0.30)' },
      listening:   { emoji: '👂', c1: '#5eead4', c2: '#10b981', glow: 'rgba(16, 185, 129, 0.38)' },
      thinking:    { emoji: '🤔', c1: '#fde047', c2: '#f59e0b', glow: 'rgba(245, 158, 11, 0.34)' },
      happy:       { emoji: '🙂', c1: '#6ee7b7', c2: '#06b6d4', glow: 'rgba(52, 211, 153, 0.36)' },
      laughing:    { emoji: '😆', c1: '#bef264', c2: '#10b981', glow: 'rgba(132, 204, 22, 0.36)' },
      funny:       { emoji: '😂', c1: '#67e8f9', c2: '#22d3ee', glow: 'rgba(34, 211, 238, 0.38)' },
      sad:         { emoji: '😔', c1: '#93c5fd', c2: '#3b82f6', glow: 'rgba(59, 130, 246, 0.28)' },
      angry:       { emoji: '😠', c1: '#fca5a5', c2: '#ef4444', glow: 'rgba(239, 68, 68, 0.40)' },
      crying:      { emoji: '😭', c1: '#7dd3fc', c2: '#2563eb', glow: 'rgba(37, 99, 235, 0.34)' },
      loving:      { emoji: '😍', c1: '#f9a8d4', c2: '#ec4899', glow: 'rgba(236, 72, 153, 0.40)' },
      embarrassed: { emoji: '😳', c1: '#fda4af', c2: '#fb7185', glow: 'rgba(251, 113, 133, 0.34)' },
      surprised:   { emoji: '😲', c1: '#fde68a', c2: '#38bdf8', glow: 'rgba(56, 189, 248, 0.40)' },
      shocked:     { emoji: '😱', c1: '#fdba74', c2: '#f97316', glow: 'rgba(249, 115, 22, 0.40)' },
      winking:     { emoji: '😉', c1: '#67e8f9', c2: '#818cf8', glow: 'rgba(129, 140, 248, 0.36)' },
      cool:        { emoji: '😎', c1: '#a5b4fc', c2: '#6366f1', glow: 'rgba(99, 102, 241, 0.38)' },
      relaxed:     { emoji: '😌', c1: '#99f6e4', c2: '#14b8a6', glow: 'rgba(20, 184, 166, 0.30)' },
      delicious:   { emoji: '🤤', c1: '#fcd34d', c2: '#fb923c', glow: 'rgba(251, 146, 60, 0.36)' },
      kissy:       { emoji: '😘', c1: '#f9a8d4', c2: '#f43f5e', glow: 'rgba(244, 63, 94, 0.38)' },
      confident:   { emoji: '😏', c1: '#c4b5fd', c2: '#8b5cf6', glow: 'rgba(139, 92, 246, 0.36)' },
      sleepy:      { emoji: '😴', c1: '#a5b4fc', c2: '#4f46e5', glow: 'rgba(79, 70, 229, 0.25)' },
      silly:       { emoji: '😜', c1: '#f0abfc', c2: '#d946ef', glow: 'rgba(217, 70, 239, 0.36)' },
      confused:    { emoji: '🙄', c1: '#cbd5e1', c2: '#64748b', glow: 'rgba(148, 163, 184, 0.30)' }
    };

    var currentEmotion = 'neutral';
    var isSpeaking = false;
    var subtitleTimer = null;
    var idleFaceTimer = null;
    var hudDimTimer = null;
    var wakeLockRef = null;

    function updateClock() {
      var now = new Date();
      var hh = String(now.getHours()).padStart(2, '0');
      var mm = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('clockTime').textContent = hh + ':' + mm;
      var days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
      document.getElementById('clockDateText').textContent =
        days[now.getDay()] + ', ' + String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear();
    }

    function heartPath(cx, cy, s) {
      return 'M ' + cx + ' ' + (cy + s * 0.7)
        + ' C ' + (cx - s * 1.3) + ' ' + (cy - s * 0.1) + ', ' + (cx - s * 1.1) + ' ' + (cy - s * 1.1) + ', ' + cx + ' ' + (cy - s * 0.35)
        + ' C ' + (cx + s * 1.1) + ' ' + (cy - s * 1.1) + ', ' + (cx + s * 1.3) + ' ' + (cy - s * 0.1) + ', ' + cx + ' ' + (cy + s * 0.7) + ' Z';
    }

    function buildFaceSvg(emotion) {
      var fill = 'url(#eyeGrad)';
      var stroke = 'url(#eyeGrad)';
      var html = '';

      if (emotion === 'happy') {
        html += '<path class="eye-group eye-left" d="M 88 120 Q 128 66 168 120" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round"/>';
        html += '<path class="eye-group eye-right" d="M 232 120 Q 272 66 312 120" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round"/>';
        html += '<ellipse cx="86" cy="144" rx="18" ry="8" fill="#fb7185" opacity="0.35"/>';
        html += '<ellipse cx="314" cy="144" rx="18" ry="8" fill="#fb7185" opacity="0.35"/>';
        html += '<path d="M 176 154 Q 200 174 224 154" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
      } else if (emotion === 'laughing' || emotion === 'funny') {
        html += '<path d="M 86 116 L 128 84 L 170 116" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>';
        html += '<path d="M 230 116 L 272 84 L 314 116" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>';
        html += '<path d="M 162 146 Q 200 192 238 146 Z" fill="' + fill + '" opacity="0.9"/>';
        if (emotion === 'funny') {
          html += '<ellipse class="anim-tear" cx="76" cy="128" rx="7" ry="12" fill="#38bdf8"/>';
          html += '<ellipse class="anim-tear" cx="324" cy="128" rx="7" ry="12" fill="#38bdf8"/>';
        }
      } else if (emotion === 'loving' || emotion === 'kissy') {
        html += '<path class="anim-heart" d="' + heartPath(128, 108, 36) + '" fill="' + fill + '"/>';
        html += '<path class="anim-heart" d="' + heartPath(272, 108, 36) + '" fill="' + fill + '"/>';
        if (emotion === 'kissy') {
          html += '<path d="M 188 156 Q 202 148 206 156 Q 202 164 188 160 Q 202 166 206 172 Q 198 178 186 172" fill="none" stroke="' + stroke + '" stroke-width="6" stroke-linecap="round"/>';
        } else {
          html += '<path d="M 178 158 Q 200 176 222 158" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
        }
      } else if (emotion === 'sad' || emotion === 'crying') {
        html += '<rect class="eye-group eye-left" x="92" y="78" width="72" height="64" rx="28" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="236" y="78" width="72" height="64" rx="28" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="122" cy="112" r="8" fill="#ffffff" opacity="0.8"/>';
        html += '<circle class="eye-pupil" cx="266" cy="112" r="8" fill="#ffffff" opacity="0.8"/>';
        html += '<path d="M 84 96 L 172 68 L 172 50 L 84 50 Z" fill="#04060c"/>';
        html += '<path d="M 316 96 L 228 68 L 228 50 L 316 50 Z" fill="#04060c"/>';
        html += '<path d="M 176 170 Q 200 152 224 170" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
        if (emotion === 'crying') {
          html += '<ellipse class="anim-tear" cx="116" cy="148" rx="6" ry="12" fill="#38bdf8"/>';
          html += '<ellipse class="anim-tear" cx="284" cy="148" rx="6" ry="12" fill="#38bdf8"/>';
        }
      } else if (emotion === 'angry') {
        html += '<rect class="eye-group eye-left" x="90" y="78" width="76" height="62" rx="22" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="234" y="78" width="76" height="62" rx="22" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="128" cy="114" r="8" fill="#ffffff" opacity="0.85"/>';
        html += '<circle class="eye-pupil" cx="272" cy="114" r="8" fill="#ffffff" opacity="0.85"/>';
        html += '<path d="M 84 62 L 174 102 L 174 52 L 84 52 Z" fill="#04060c"/>';
        html += '<path d="M 316 62 L 226 102 L 226 52 L 316 52 Z" fill="#04060c"/>';
        html += '<path d="M 172 168 Q 200 154 228 168" fill="none" stroke="' + stroke + '" stroke-width="8" stroke-linecap="round"/>';
      } else if (emotion === 'surprised' || emotion === 'shocked') {
        html += '<circle class="eye-group eye-left" cx="128" cy="104" r="42" fill="' + fill + '"/>';
        html += '<circle class="eye-group eye-right" cx="272" cy="104" r="42" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="128" cy="104" r="14" fill="#04060c"/>';
        html += '<circle class="eye-pupil" cx="272" cy="104" r="14" fill="#04060c"/>';
        html += '<circle class="eye-pupil" cx="122" cy="96" r="6" fill="#ffffff" opacity="0.9"/>';
        html += '<circle class="eye-pupil" cx="266" cy="96" r="6" fill="#ffffff" opacity="0.9"/>';
        html += '<ellipse cx="200" cy="168" rx="' + (emotion === 'shocked' ? 16 : 12) + '" ry="' + (emotion === 'shocked' ? 22 : 16) + '" fill="none" stroke="' + stroke + '" stroke-width="7"/>';
      } else if (emotion === 'thinking') {
        html += '<rect class="eye-group eye-left" x="92" y="72" width="72" height="72" rx="30" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="236" y="88" width="72" height="44" rx="20" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="136" cy="96" r="10" fill="#ffffff" opacity="0.88"/>';
        html += '<circle class="eye-pupil" cx="278" cy="104" r="8" fill="#ffffff" opacity="0.88"/>';
        html += '<circle class="anim-dot-1" cx="332" cy="56" r="6" fill="' + fill + '"/>';
        html += '<circle class="anim-dot-2" cx="352" cy="46" r="7" fill="' + fill + '"/>';
        html += '<circle class="anim-dot-3" cx="374" cy="36" r="8" fill="' + fill + '"/>';
        html += '<line x1="184" y1="164" x2="216" y2="160" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
      } else if (emotion === 'winking') {
        html += '<rect class="eye-group eye-left" x="92" y="68" width="72" height="80" rx="32" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="122" cy="98" r="10" fill="#ffffff" opacity="0.85"/>';
        html += '<path class="eye-group eye-right" d="M 232 114 Q 272 74 312 114" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round"/>';
        html += '<path d="M 178 158 Q 204 176 226 154" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
      } else if (emotion === 'cool') {
        html += '<rect x="74" y="74" width="106" height="58" rx="16" fill="#0f172a" stroke="' + stroke + '" stroke-width="8"/>';
        html += '<rect x="220" y="74" width="106" height="58" rx="16" fill="#0f172a" stroke="' + stroke + '" stroke-width="8"/>';
        html += '<line x1="180" y1="98" x2="220" y2="98" stroke="' + stroke + '" stroke-width="10"/>';
        html += '<line x1="92" y1="118" x2="128" y2="84" stroke="#ffffff" stroke-width="5" opacity="0.45" stroke-linecap="round"/>';
        html += '<line x1="238" y1="118" x2="274" y2="84" stroke="#ffffff" stroke-width="5" opacity="0.45" stroke-linecap="round"/>';
        html += '<path d="M 178 162 Q 204 174 228 154" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
      } else if (emotion === 'relaxed') {
        html += '<path d="M 88 106 Q 128 138 168 106" fill="none" stroke="' + stroke + '" stroke-width="16" stroke-linecap="round"/>';
        html += '<path d="M 232 106 Q 272 138 312 106" fill="none" stroke="' + stroke + '" stroke-width="16" stroke-linecap="round"/>';
        html += '<path d="M 180 158 Q 200 172 220 158" fill="none" stroke="' + stroke + '" stroke-width="6" stroke-linecap="round"/>';
      } else if (emotion === 'sleepy') {
        html += '<rect x="90" y="102" width="76" height="26" rx="13" fill="' + fill + '" opacity="0.78"/>';
        html += '<rect x="234" y="102" width="76" height="26" rx="13" fill="' + fill + '" opacity="0.78"/>';
        html += '<text class="anim-zzz-1" x="326" y="78" fill="' + fill + '" font-size="24" font-weight="700">Z</text>';
        html += '<text class="anim-zzz-2" x="348" y="58" fill="' + fill + '" font-size="18" font-weight="700">z</text>';
        html += '<circle cx="200" cy="162" r="8" fill="none" stroke="' + stroke + '" stroke-width="5" opacity="0.7"/>';
      } else if (emotion === 'embarrassed') {
        html += '<rect class="eye-group eye-left" x="94" y="74" width="68" height="72" rx="30" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="238" y="74" width="68" height="72" rx="30" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="124" cy="106" r="9" fill="#ffffff" opacity="0.85"/>';
        html += '<circle class="eye-pupil" cx="268" cy="106" r="9" fill="#ffffff" opacity="0.85"/>';
        html += '<ellipse cx="86" cy="148" rx="22" ry="10" fill="#fb7185" opacity="0.55"/>';
        html += '<ellipse cx="314" cy="148" rx="22" ry="10" fill="#fb7185" opacity="0.55"/>';
        html += '<path d="M 186 164 Q 194 158 200 164 Q 206 170 214 164" fill="none" stroke="' + stroke + '" stroke-width="6" stroke-linecap="round"/>';
      } else if (emotion === 'delicious' || emotion === 'silly') {
        html += '<path d="M 88 114 Q 128 74 168 114" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round"/>';
        if (emotion === 'silly') {
          html += '<circle class="eye-group eye-right" cx="272" cy="104" r="36" fill="' + fill + '"/>';
          html += '<circle class="eye-pupil" cx="278" cy="98" r="10" fill="#ffffff" opacity="0.88"/>';
        } else {
          html += '<path d="M 232 114 Q 272 74 312 114" fill="none" stroke="' + stroke + '" stroke-width="18" stroke-linecap="round"/>';
        }
        html += '<path d="M 174 152 Q 200 168 226 152" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
        html += '<path d="M 194 159 Q 206 188 218 158 Z" fill="#fb7185"/>';
      } else if (emotion === 'confident') {
        html += '<rect class="eye-group eye-left" x="92" y="84" width="74" height="54" rx="22" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="234" y="72" width="74" height="66" rx="26" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="128" cy="108" r="8" fill="#ffffff" opacity="0.85"/>';
        html += '<circle class="eye-pupil" cx="270" cy="100" r="9" fill="#ffffff" opacity="0.85"/>';
        html += '<path d="M 176 164 Q 208 168 230 148" fill="none" stroke="' + stroke + '" stroke-width="7" stroke-linecap="round"/>';
      } else if (emotion === 'confused') {
        html += '<circle class="eye-group eye-left" cx="128" cy="106" r="34" fill="' + fill + '"/>';
        html += '<circle class="eye-group eye-right" cx="272" cy="98" r="26" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="132" cy="98" r="9" fill="#ffffff" opacity="0.85"/>';
        html += '<circle class="eye-pupil" cx="276" cy="92" r="7" fill="#ffffff" opacity="0.85"/>';
        html += '<text x="332" y="64" fill="' + fill + '" font-size="34" font-weight="700">?</text>';
        html += '<path d="M 180 166 Q 200 156 220 166" fill="none" stroke="' + stroke + '" stroke-width="6" stroke-linecap="round"/>';
      } else if (emotion === 'listening') {
        html += '<rect class="eye-group eye-left" x="88" y="64" width="80" height="88" rx="36" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="232" y="64" width="80" height="88" rx="36" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="122" cy="98" r="12" fill="#ffffff" opacity="0.92"/>';
        html += '<circle class="eye-pupil" cx="266" cy="98" r="12" fill="#ffffff" opacity="0.92"/>';
      } else {
        // neutral default
        html += '<rect class="eye-group eye-left" x="92" y="68" width="74" height="80" rx="34" fill="' + fill + '"/>';
        html += '<rect class="eye-group eye-right" x="234" y="68" width="74" height="80" rx="34" fill="' + fill + '"/>';
        html += '<circle class="eye-pupil" cx="124" cy="98" r="10" fill="#ffffff" opacity="0.88"/>';
        html += '<circle class="eye-pupil" cx="266" cy="98" r="10" fill="#ffffff" opacity="0.88"/>';
      }
      return html;
    }

    var ALL_WRAP_ANIMS = [
      'anim-float', 'anim-happy', 'anim-laugh', 'anim-love', 'anim-sad',
      'anim-angry', 'anim-think', 'anim-sleepy', 'anim-listen', 'anim-shy',
      'anim-cool', 'anim-relax', 'anim-shock'
    ];
    var EMOTION_WRAP_ANIM = {
      neutral: 'anim-float',
      listening: 'anim-listen',
      thinking: 'anim-think',
      happy: 'anim-happy',
      laughing: 'anim-laugh',
      funny: 'anim-laugh',
      loving: 'anim-love',
      kissy: 'anim-love',
      sad: 'anim-sad',
      crying: 'anim-sad',
      angry: 'anim-angry',
      surprised: 'anim-happy',
      shocked: 'anim-shock',
      embarrassed: 'anim-shy',
      winking: 'anim-happy',
      cool: 'anim-cool',
      relaxed: 'anim-relax',
      delicious: 'anim-shy',
      confident: 'anim-cool',
      sleepy: 'anim-sleepy',
      silly: 'anim-shy',
      confused: 'anim-think'
    };
    var morphSwapTimer = null;

    function applyEmotion(code, emojiOverride) {
      code = String(code || 'neutral').trim().toLowerCase();
      if (!EMOTIONS[code]) code = 'neutral';
      var changed = (code !== currentEmotion) || !document.getElementById('faceCanvas').innerHTML;
      currentEmotion = code;
      var meta = EMOTIONS[code];

      document.documentElement.style.setProperty('--accent', meta.c1);
      document.documentElement.style.setProperty('--accent-2', meta.c2);
      document.documentElement.style.setProperty('--glow', meta.glow);
      document.getElementById('gradStop1').setAttribute('stop-color', meta.c1);
      document.getElementById('gradStop2').setAttribute('stop-color', meta.c2);

      var canvas = document.getElementById('faceCanvas');
      if (changed && canvas) {
        if (morphSwapTimer) clearTimeout(morphSwapTimer);
        canvas.classList.remove('morph-in');
        canvas.classList.add('morph-out');
        morphSwapTimer = setTimeout(function () {
          canvas.innerHTML = buildFaceSvg(code);
          canvas.classList.remove('morph-out');
          void canvas.offsetWidth;
          canvas.classList.add('morph-in');
        }, 110);
      }

      document.getElementById('emotionEmoji').textContent = emojiOverride || meta.emoji;
      document.getElementById('emotionCode').textContent = code;

      var wrap = document.getElementById('faceWrap');
      ALL_WRAP_ANIMS.forEach(function (cls) { wrap.classList.remove(cls); });
      wrap.classList.add(EMOTION_WRAP_ANIM[code] || 'anim-float');

      var wave = document.getElementById('listeningWave');
      wave.classList.toggle('active', code === 'listening');

      document.querySelectorAll('.demo-chip').forEach(function (el) {
        el.classList.toggle('active', el.dataset.emo === code);
      });
    }

    function applySpeaking(active) {
      isSpeaking = Boolean(active);
      var wrap = document.getElementById('faceWrap');
      var pill = document.getElementById('speakingPill');
      var demoBtn = document.getElementById('btnDemoSpeak');
      if (wrap) wrap.classList.toggle('is-speaking', isSpeaking);
      if (pill) pill.classList.toggle('active', isSpeaking);
      if (demoBtn) {
        demoBtn.innerHTML = isSpeaking
          ? '<i class="bi bi-volume-mute"></i> Dừng Nói'
          : '<i class="bi bi-volume-up"></i> Thử Nói';
      }
    }

    function toggleDemoSpeaking() {
      applySpeaking(!isSpeaking);
    }

    function toggleDemoDisconnect() {
      var banner = document.getElementById('sseAlertBanner');
      if (!banner) return;
      var willShow = banner.classList.contains('hidden');
      showSseDisconnectBanner(willShow);
      setSseStatus(willShow ? 'off' : 'online', willShow ? 'Mất kết nối' : 'SSE Live');
      var btn = document.getElementById('btnDemoDisconnect');
      if (btn) {
        btn.innerHTML = willShow
          ? '<i class="bi bi-wifi"></i> Khôi Phục Kết Nối'
          : '<i class="bi bi-wifi-off"></i> Thử Mất Kết Nối';
      }
      if (willShow) {
        var drawer = document.getElementById('demoDrawer');
        if (drawer && drawer.classList.contains('open')) {
          drawer.classList.remove('open');
        }
      }
    }

    function applyStatePayload(data) {
      if (!data || typeof data !== 'object') return;
      applyEmotion(data.emotion || 'neutral', data.emoji);
      applySpeaking(Boolean(data.speaking));

      if (idleFaceTimer) clearTimeout(idleFaceTimer);
      if (!isSpeaking && currentEmotion !== 'neutral' && currentEmotion !== 'listening' && currentEmotion !== 'thinking') {
        idleFaceTimer = setTimeout(function () {
          if (!isSpeaking && currentEmotion !== 'listening' && currentEmotion !== 'thinking') {
            applyEmotion('neutral');
          }
        }, 15000);
      }

      var subBox = document.getElementById('subtitleBox');
      var subHuman = document.getElementById('subHuman');
      var subBot = document.getElementById('subBot');
      var hText = String(data.human_text || '').trim();
      var bText = String(data.bot_text || '').trim();

      if (hText || bText) {
        var textChanged = (subBot.textContent !== bText) || (subHuman.textContent !== (hText ? ('“' + hText + '”') : ''));
        subHuman.textContent = hText ? ('“' + hText + '”') : '';
        subBot.textContent = bText;
        if (bText.length > 140) {
          subBot.classList.add('long-text');
        } else {
          subBot.classList.remove('long-text');
        }
        subBox.classList.remove('hidden-soft');

        if (textChanged) {
          if (window._kbSubScrollTimer) {
            clearInterval(window._kbSubScrollTimer);
            window._kbSubScrollTimer = null;
          }
          if (window._kbSubScrollDelay) {
            clearTimeout(window._kbSubScrollDelay);
            window._kbSubScrollDelay = null;
          }
          subBox.scrollTop = 0;
          if (subBox.scrollHeight > subBox.clientHeight + 8) {
            // Tự động cuộn chậm kiểu máy nhắc chữ (teleprompter) để đọc trọn câu TTS dài
            var userPausedScroll = false;
            subBox.onwheel = function () { userPausedScroll = true; };
            subBox.ontouchstart = function () { userPausedScroll = true; };
            window._kbSubScrollDelay = setTimeout(function () {
              window._kbSubScrollTimer = setInterval(function () {
                if (userPausedScroll || subBox.classList.contains('hidden-soft')) {
                  clearInterval(window._kbSubScrollTimer);
                  window._kbSubScrollTimer = null;
                  return;
                }
                if (subBox.scrollTop + subBox.clientHeight >= subBox.scrollHeight - 2) {
                  clearInterval(window._kbSubScrollTimer);
                  window._kbSubScrollTimer = null;
                  return;
                }
                subBox.scrollTop += 1;
              }, 55);
            }, 1800);
          }
        }

        if (subtitleTimer) clearTimeout(subtitleTimer);
        var hideDelay = Math.max(12000, Math.min(45000, bText.length * 85 + 6000));
        subtitleTimer = setTimeout(function () {
          if (!isSpeaking && currentEmotion !== 'listening' && currentEmotion !== 'thinking') {
            subBox.classList.add('hidden-soft');
          }
        }, hideDelay);
      }

      var mediaPill = document.getElementById('mediaPill');
      var mediaTitle = document.getElementById('mediaTitle');
      var mediaCoverWrap = document.getElementById('mediaCoverWrap');
      var mediaCover = document.getElementById('mediaCover');
      if (data.media_playing && data.media_title) {
        mediaTitle.textContent = String(data.media_title);
        var rawCover = String(data.media_cover || '').trim();
        var isSafeCover = rawCover && (/^https?:\/\//i.test(rawCover) || rawCover.charAt(0) === '/');
        if (isSafeCover && mediaCover && mediaCoverWrap) {
          if (mediaCover.getAttribute('data-cover-src') !== rawCover) {
            mediaCover.setAttribute('data-cover-src', rawCover);
            mediaCover.onerror = function () {
              mediaCoverWrap.classList.remove('has-cover');
              mediaPill.classList.add('no-cover');
            };
            mediaCover.onload = function () {
              mediaCoverWrap.classList.add('has-cover');
              mediaPill.classList.remove('no-cover');
            };
            mediaCover.src = rawCover;
          } else if (mediaCover.complete && mediaCover.naturalWidth > 0) {
            mediaCoverWrap.classList.add('has-cover');
            mediaPill.classList.remove('no-cover');
          }
        } else if (mediaCoverWrap) {
          mediaCoverWrap.classList.remove('has-cover');
          mediaPill.classList.add('no-cover');
        }
        mediaPill.classList.add('visible');
        faceMediaPaused = Boolean(data.media_paused);
        mediaPill.classList.toggle('is-paused', faceMediaPaused);
        var mStatusLbl = document.getElementById('mediaStatusLabel');
        if (mStatusLbl) mStatusLbl.textContent = faceMediaPaused ? 'Tạm dừng' : 'Đang phát';
        var mIcon = document.getElementById('mediaPlayPauseIcon');
        if (mIcon) mIcon.className = faceMediaPaused ? 'bi bi-play-fill' : 'bi bi-pause-fill';
      } else {
        faceMediaPaused = false;
        mediaPill.classList.remove('visible');
        mediaPill.classList.remove('is-paused');
      }

      if (Array.isArray(data.timers)) {
        syncFaceTimers(data.timers);
      }
    }

    // Natural random blink & subtle idle saccade
    function scheduleBlink() {
      var delay = 2600 + Math.random() * 3400;
      setTimeout(function () {
        var wrap = document.getElementById('faceWrap');
        if (wrap && ['neutral', 'listening', 'sad', 'embarrassed', 'confident', 'surprised'].indexOf(currentEmotion) !== -1) {
          wrap.classList.add('blinking');
          setTimeout(function () { wrap.classList.remove('blinking'); }, 140);
        }
        scheduleBlink();
      }, delay);
    }

    var pointerGazeUntil = 0;

    var lastGazeDx = 0;
    var lastGazeDy = 0;

    function setEyeGaze(dx, dy) {
      lastGazeDx = dx;
      lastGazeDy = dy;
      var gaze = document.getElementById('eyeGaze');
      if (gaze) {
        gaze.style.transform = 'translate(' + Math.round(dx * 0.65) + 'px, ' + Math.round(dy * 0.65) + 'px)';
      }
      var px = Math.max(-14, Math.min(14, Math.round(dx * 0.65)));
      var py = Math.max(-10, Math.min(10, Math.round(dy * 0.65)));
      document.querySelectorAll('#faceCanvas .eye-pupil').forEach(function (el) {
        el.style.transform = 'translate(' + px + 'px, ' + py + 'px)';
      });
    }

    function scheduleIdleSaccade() {
      var step = function () {
        var now = Date.now();
        if (now >= pointerGazeUntil) {
          var dx = 0, dy = 0;
          if (currentEmotion === 'thinking' || currentEmotion === 'confused') {
            dx = (Math.random() > 0.5 ? 1 : -1) * (10 + Math.random() * 10);
            dy = -(6 + Math.random() * 6);
          } else if (currentEmotion === 'sad' || currentEmotion === 'crying' || currentEmotion === 'embarrassed') {
            dx = (Math.random() - 0.5) * 20;
            dy = 5 + Math.random() * 6;
          } else if (['neutral', 'listening', 'happy', 'confident', 'winking', 'cool', 'surprised'].indexOf(currentEmotion) !== -1) {
            var r = Math.random();
            if (r < 0.35) {
              dx = 0; dy = 0;
            } else {
              dx = Math.round((Math.random() - 0.5) * 32);
              dy = Math.round((Math.random() - 0.5) * 14);
            }
          }
          setEyeGaze(dx, dy);
        }
        setTimeout(step, 1800 + Math.random() * 2400);
      };
      setTimeout(step, 1500);
    }

    function trackPointerGaze(clientX, clientY) {
      if (['sleepy', 'relaxed', 'laughing', 'funny'].indexOf(currentEmotion) !== -1) return;
      var nx = (clientX / Math.max(1, window.innerWidth) - 0.5) * 2;
      var ny = (clientY / Math.max(1, window.innerHeight) - 0.5) * 2;
      var dx = Math.max(-22, Math.min(22, Math.round(nx * 22)));
      var dy = Math.max(-12, Math.min(12, Math.round(ny * 12)));
      pointerGazeUntil = Date.now() + 3000;
      setEyeGaze(dx, dy);
    }

    function setSseStatus(state, text) {
      var dot = document.getElementById('sseStatusDot');
      var label = document.getElementById('sseStatusText');
      if (dot) dot.className = 'status-dot ' + state;
      if (label) label.textContent = text;
    }

    function showSseDisconnectBanner(show) {
      var banner = document.getElementById('sseAlertBanner');
      if (!banner) return;
      if (show) {
        banner.classList.remove('hidden');
        banner.style.setProperty('display', 'flex', 'important');
        banner.style.setProperty('top', 'auto', 'important');
        banner.style.setProperty('bottom', '20px', 'important');
      } else {
        banner.classList.add('hidden');
        banner.style.setProperty('display', 'none', 'important');
      }
    }

    var faceEventSource = null;
    var sseReconnectTimer = null;

    function connectFaceSse() {
      if (!VBOT_FACE_SSE_ACTIVE) {
        setSseStatus('off', 'SSE đang tắt');
        showSseDisconnectBanner(false);
        return;
      }
      if (sseReconnectTimer) {
        clearTimeout(sseReconnectTimer);
        sseReconnectTimer = null;
      }
      if (faceEventSource) {
        try { faceEventSource.close(); } catch (_) {}
        faceEventSource = null;
      }
      var proto = (window.location.protocol === 'https:') ? 'https:' : 'http:';
      var sseUrl = proto + '//' + window.location.hostname + ':' + VBOT_API_PORT + '/ai-face/events';
      if (VBOT_API_KEY) {
        sseUrl += '?api_key=' + encodeURIComponent(VBOT_API_KEY);
      }
      setSseStatus('warn', 'Đang kết nối SSE...');
      try {
        faceEventSource = new EventSource(sseUrl);
        faceEventSource.onopen = function () {
          setSseStatus('online', 'SSE Live');
          showSseDisconnectBanner(false);
        };
        faceEventSource.onmessage = function (ev) {
          showSseDisconnectBanner(false);
          if (!ev || !ev.data) return;
          try {
            applyStatePayload(JSON.parse(ev.data));
          } catch (_) {}
        };
        faceEventSource.onerror = function () {
          setSseStatus('off', 'Mất kết nối');
          showSseDisconnectBanner(true);
          if (faceEventSource && faceEventSource.readyState === EventSource.CLOSED) {
            if (!sseReconnectTimer) {
              sseReconnectTimer = setTimeout(connectFaceSse, 3000);
            }
          }
        };
      } catch (_) {
        setSseStatus('off', 'Lỗi SSE');
        showSseDisconnectBanner(true);
        if (!sseReconnectTimer) {
          sseReconnectTimer = setTimeout(connectFaceSse, 4000);
        }
      }
    }

    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(function () {});
      } else {
        document.exitFullscreen().catch(function () {});
      }
    }

    function toggleDemoDrawer() {
      document.getElementById('demoDrawer').classList.toggle('open');
    }

    function initDemoDrawer() {
      var grid = document.getElementById('demoGrid');
      Object.keys(EMOTIONS).forEach(function (code) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'demo-chip';
        btn.dataset.emo = code;
        btn.textContent = EMOTIONS[code].emoji + ' ' + code;
        btn.onclick = function () { applyEmotion(code); };
        grid.appendChild(btn);
      });
    }

    async function requestWakeLock() {
      try {
        if ('wakeLock' in navigator) {
          wakeLockRef = await navigator.wakeLock.request('screen');
        }
      } catch (_) {}
    }

    var faceToastTimer = null;

    function showFaceToast(msg, type, durationMs) {
      var el = document.getElementById('faceToast');
      if (!el) return;
      var icon = type === 'ok'
        ? '<i class="bi bi-check-circle-fill" style="color:#22c55e;"></i>'
        : (type === 'err'
          ? '<i class="bi bi-exclamation-octagon-fill" style="color:#ef4444;"></i>'
          : '<i class="bi bi-arrow-repeat" style="color:#38e8ff;"></i>');
      el.className = 'face-toast ' + (type || 'info') + ' show';
      el.innerHTML = icon + '<span></span>';
      el.querySelector('span').textContent = String(msg || '');
      if (faceToastTimer) clearTimeout(faceToastTimer);
      if (durationMs !== 0) {
        faceToastTimer = setTimeout(function () {
          el.classList.remove('show');
        }, durationMs || 4000);
      }
    }

    function closePowerMenu() {
      var dd = document.getElementById('powerDropdown');
      var btn = document.getElementById('powerMenuBtn');
      if (dd) dd.classList.remove('open');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function togglePowerMenu(ev) {
      if (ev) ev.stopPropagation();
      var dd = document.getElementById('powerDropdown');
      var btn = document.getElementById('powerMenuBtn');
      if (!dd) return;
      var willOpen = !dd.classList.contains('open');
      dd.classList.toggle('open', willOpen);
      if (btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      resetHudDimTimer();
    }

    function power_action_service(action_name, mess_name) {
      closePowerMenu();
      if (!confirm(mess_name)) {
        return;
      }
      showFaceToast('Đang thực hiện yêu cầu...', 'info', 0);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== XMLHttpRequest.DONE) return;
        if (xhr.status === 200) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response && response.success) {
              showFaceToast(response.message || 'Thao tác thành công.', 'ok', 4500);
              if (VBOT_FACE_SSE_ACTIVE && (action_name === 'start_vbot_service' || action_name === 'restart_vbot_service')) {
                setTimeout(connectFaceSse, 2500);
              }
            } else {
              showFaceToast('Lỗi: ' + ((response && response.message) ? response.message : 'Không rõ nguyên nhân'), 'err', 5500);
            }
          } catch (_) {
            showFaceToast('Lỗi định dạng phản hồi từ máy chủ.', 'err', 5500);
          }
        } else if (xhr.status === 403) {
          showFaceToast('Phiên làm việc hết hạn (CSRF), đang tải lại trang...', 'err', 3000);
          setTimeout(function () { window.location.reload(); }, 1500);
        } else {
          var errMsg = 'Yêu cầu thất bại (HTTP ' + xhr.status + ')';
          try {
            var errJson = JSON.parse(xhr.responseText);
            if (errJson && errJson.message) errMsg = 'Lỗi: ' + errJson.message;
          } catch (_) {}
          showFaceToast(errMsg, 'err', 5500);
        }
      };
      xhr.onerror = function () {
        showFaceToast('Không thể kết nối tới máy chủ WebUI.', 'err', 5000);
      };
      var body = encodeURIComponent(action_name) + '=1&csrf_token=' + encodeURIComponent(window.VBOT_CSRF_TOKEN || '');
      xhr.send(body);
    }

    function resetHudDimTimer() {
      var hud = document.getElementById('hudControls');
      if (!hud) return;
      hud.classList.remove('dimmed');
      if (hudDimTimer) clearTimeout(hudDimTimer);
      hudDimTimer = setTimeout(function () {
        var dd = document.getElementById('powerDropdown');
        if (dd && dd.classList.contains('open')) return;
        hud.classList.add('dimmed');
      }, 5000);
    }

    var faceMediaPaused = false;
    var faceActiveTimers = [];
    var FACE_WEATHER_LAT = <?php echo json_encode($weatherLat, JSON_UNESCAPED_UNICODE); ?>;
    var FACE_WEATHER_LON = <?php echo json_encode($weatherLon, JSON_UNESCAPED_UNICODE); ?>;
    var FACE_API_URL = <?php echo json_encode((string)($URL_API_VBOT ?? ''), JSON_UNESCAPED_UNICODE); ?>;
    var FACE_API_KEY = <?php echo json_encode($apiKeyParam, JSON_UNESCAPED_UNICODE); ?>;

    // 1. Thời tiết gọn bên dưới đồng hồ (dùng đúng cơ chế OpenWeatherMap giống index.php)
    async function loadFaceWeather() {
      if (!FACE_WEATHER_LAT || !FACE_WEATHER_LON) return;
      try {
        var weatherUrl = 'https://api.openweathermap.org/data/2.5/weather?lat=' + encodeURIComponent(FACE_WEATHER_LAT)
          + '&lon=' + encodeURIComponent(FACE_WEATHER_LON)
          + '&appid=8473858601dabd3d2cbb24fb50840686&units=metric&lang=vi';
        var ctrl = new AbortController();
        var tId = setTimeout(function () { ctrl.abort(); }, 15000);
        var res = await fetch(weatherUrl, { signal: ctrl.signal });
        clearTimeout(tId);
        if (!res.ok) return;
        var w = await res.json();
        var pill = document.getElementById('weatherPill');
        var elTemp = document.getElementById('faceWeatherTemp');
        var elDesc = document.getElementById('faceWeatherDesc');
        var elIcon = document.getElementById('faceWeatherIcon');
        if (w && w.main && elTemp && pill) {
          elTemp.textContent = Math.round(Number(w.main.temp)) + '°C';
          if (elDesc && w.weather && w.weather[0]) {
            elDesc.textContent = String(w.weather[0].description || '');
          }
          if (elIcon && w.weather && w.weather[0] && w.weather[0].icon) {
            elIcon.src = 'https://openweathermap.org/img/w/' + encodeURIComponent(w.weather[0].icon) + '.png';
          }
          pill.classList.add('visible');
        }
      } catch (_) {}
    }

    // 2. Hiển thị bộ đếm ngược & hẹn giờ tắt nhạc trực tiếp
    function syncFaceTimers(rawTimers) {
      var nowMs = Date.now();
      faceActiveTimers = (Array.isArray(rawTimers) ? rawTimers : [])
        .filter(function (t) { return t && Number(t.remaining_sec) > 0; })
        .map(function (t) {
          return {
            kind: String(t.kind || 'countdown'),
            label: String(t.label || 'Hẹn giờ'),
            endAtMs: nowMs + Number(t.remaining_sec) * 1000
          };
        });
      renderFaceTimers();
    }

    function formatTimerDuration(totalSec) {
      var s = Math.max(0, Math.floor(totalSec));
      var h = Math.floor(s / 3600);
      var m = Math.floor((s % 3600) / 60);
      var sec = s % 60;
      var pad = function (n) { return n < 10 ? '0' + n : String(n); };
      return h > 0 ? (pad(h) + ':' + pad(m) + ':' + pad(sec)) : (pad(m) + ':' + pad(sec));
    }

    function renderFaceTimers() {
      var box = document.getElementById('timerBadges');
      if (!box) return;
      var nowMs = Date.now();
      faceActiveTimers = faceActiveTimers.filter(function (t) { return t.endAtMs > nowMs; });
      if (!faceActiveTimers.length) {
        if (box.childNodes.length) box.replaceChildren();
        return;
      }
      box.replaceChildren();
      faceActiveTimers.slice(0, 3).forEach(function (t) {
        var span = document.createElement('span');
        span.className = 'timer-badge' + (t.kind === 'sleep' ? ' sleep-timer' : '');
        var icon = document.createElement('i');
        icon.className = t.kind === 'sleep' ? 'bi bi-moon-fill' : 'bi bi-stopwatch';
        var rem = Math.ceil((t.endAtMs - nowMs) / 1000);
        span.appendChild(icon);
        span.appendChild(document.createTextNode(' ' + t.label + ' ' + formatTimerDuration(rem)));
        box.appendChild(span);
      });
    }

    // 3. Điều khiển nhạc chạm nhanh trên .media-pill (tự thích ứng LAN IP, mDNS .local và Reverse Proxy)
    function resolveFaceApiUrl() {
      if (FACE_API_URL && FACE_API_URL.charAt(0) === '/') return FACE_API_URL;
      var proto = (window.location.protocol === 'https:') ? 'https:' : 'http:';
      return proto + '//' + window.location.hostname + ':' + VBOT_API_PORT + '/';
    }

    function faceControlMedia(action) {
      var targetUrl = resolveFaceApiUrl();
      if (!targetUrl) return;
      var headers = { 'Content-Type': 'application/json' };
      if (FACE_API_KEY) headers['VBot-API-Key'] = FACE_API_KEY;
      fetch(targetUrl, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: JSON.stringify({ type: 1, data: 'media_control', action: action })
      }).catch(function () {});
    }

    function faceTogglePlayPause() {
      var nextAction = faceMediaPaused ? 'resume' : 'pause';
      faceMediaPaused = !faceMediaPaused;
      var mediaPill = document.getElementById('mediaPill');
      if (mediaPill) mediaPill.classList.toggle('is-paused', faceMediaPaused);
      var mIcon = document.getElementById('mediaPlayPauseIcon');
      if (mIcon) mIcon.className = faceMediaPaused ? 'bi bi-play-fill' : 'bi bi-pause-fill';
      var mStatusLbl = document.getElementById('mediaStatusLabel');
      if (mStatusLbl) mStatusLbl.textContent = faceMediaPaused ? 'Tạm dừng' : 'Đang phát';
      faceControlMedia(nextAction);
    }

    // 4. Chế độ Ngủ Đêm (Tự động 22:30–06:00 / Bật / Tắt) + OLED Pixel Shift
    var NIGHT_MODES = ['auto', 'on', 'off'];
    function getSavedNightMode() {
      try {
        var m = localStorage.getItem('vbot_face_night_mode');
        if (NIGHT_MODES.indexOf(m) !== -1) return m;
      } catch (_) {}
      return 'auto';
    }

    function applyNightModeState() {
      var mode = getSavedNightMode();
      var now = new Date();
      var mins = now.getHours() * 60 + now.getMinutes();
      var isNightHour = (mins >= 22 * 60 + 30) || (mins < 6 * 60);
      var shouldDim = (mode === 'on') || (mode === 'auto' && isNightHour);
      document.body.classList.toggle('night-dim-active', shouldDim);
      var btn = document.getElementById('nightModeBtn');
      var lbl = document.getElementById('nightModeLabel');
      if (btn) btn.classList.toggle('night-active', shouldDim);
      if (lbl) {
        lbl.textContent = mode === 'on' ? 'Đêm: Bật' : (mode === 'off' ? 'Đêm: Tắt' : 'Đêm: Tự động');
      }
    }

    function cycleNightMode() {
      var cur = getSavedNightMode();
      var next = NIGHT_MODES[(NIGHT_MODES.indexOf(cur) + 1) % NIGHT_MODES.length];
      try { localStorage.setItem('vbot_face_night_mode', next); } catch (_) {}
      applyNightModeState();
    }

    var oledShiftStep = 0;
    function runOledPixelShift() {
      var offsets = [[0, 0], [3, 2], [-3, 2], [-2, -3], [3, -2]];
      oledShiftStep = (oledShiftStep + 1) % offsets.length;
      var stage = document.querySelector('.face-stage');
      if (stage) {
        stage.style.transform = 'translate(' + offsets[oledShiftStep][0] + 'px, ' + offsets[oledShiftStep][1] + 'px)';
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      updateClock();
      setInterval(updateClock, 1000);
      setInterval(renderFaceTimers, 1000);
      loadFaceWeather();
      setInterval(loadFaceWeather, 15 * 60 * 1000);
      applyNightModeState();
      setInterval(applyNightModeState, 60 * 1000);
      setInterval(runOledPixelShift, 180 * 1000);
      initDemoDrawer();
      applyEmotion('neutral');
      scheduleBlink();
      scheduleIdleSaccade();
      connectFaceSse();
      requestWakeLock();
      resetHudDimTimer();
      ['mousemove', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetHudDimTimer, { passive: true });
      });
      document.addEventListener('click', function (e) {
        var dd = document.getElementById('powerDropdown');
        if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
          closePowerMenu();
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closePowerMenu();
        }
      });
      document.addEventListener('mousemove', function (e) {
        trackPointerGaze(e.clientX, e.clientY);
      }, { passive: true });
      document.addEventListener('touchstart', function (e) {
        if (e.touches && e.touches[0]) {
          trackPointerGaze(e.touches[0].clientX, e.touches[0].clientY);
        }
      }, { passive: true });
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
          requestWakeLock();
          if (VBOT_FACE_SSE_ACTIVE && (!faceEventSource || faceEventSource.readyState !== EventSource.OPEN)) {
            connectFaceSse();
          }
        }
      });
      window.addEventListener('online', function () {
        if (VBOT_FACE_SSE_ACTIVE) {
          connectFaceSse();
        }
      });
    });
  </script>
</body>
</html>
