"use strict";

const { EventEmitter } = require("node:events");
const http = require("node:http");
const https = require("node:https");

const clampVolume = value => Math.max(0, Math.min(100, Math.round(Number(value) || 0)));

function parseSseFrame(frame) {
  const data = frame.split(/\r?\n/)
    .filter(line => line.startsWith("data:"))
    .map(line => line.slice(5).trimStart())
    .join("\n");
  if (!data) return null;
  return JSON.parse(data);
}

function normalizeState(payload = {}) {
  const media = payload.media_player || payload;
  const state = String(media.playback_state || "idle").toLowerCase();
  const output = payload.audio_output || media.audio_output || {};
  return {
    volume: clampVolume(payload.volume),
    muted: Boolean(output.muted),
    playback: state === "playing" ? "playing" : state === "paused" ? "paused" : "stopped",
    playbackRaw: state,
    title: media.media_name || media.airplay_song_name || "",
    source: media.media_player_source || "N/A",
    sourceKind: String(media.source_kind || "idle").toLowerCase(),
    micOn: payload.mic_on_off !== false,
    conversationMode: Boolean(payload.conversation_mode),
    wakeActive: Boolean(payload.wake_me_up),
    wakeupReply: Boolean(payload.wakeup_reply),
    wakeInMedia: Boolean(media.wake_up_in_media_player),
    multipleCommand: Boolean(payload.multiple_command_active),
    continueListening: Boolean(payload.continue_listening_after_commands),
    cacheTts: Boolean(payload.cache_tts_active),
    mediaPlayerActive: Boolean(media.media_player_active),
    chatGpt: Boolean(payload.chat_gpt_active),
    googleGemini: Boolean(payload.google_gemini_active),
    xiaozhi: Boolean(payload.xiaozhi_active),
    defaultAssistant: Boolean(payload.default_assistant_active),
    olli: Boolean(payload.olli_assistant_active),
    zaloAssistant: Boolean(payload.zalo_assistant_active),
    difyAi: Boolean(payload.dify_ai_active),
    devAssistant: Boolean(payload.dev_custom_assistant),
    processing: Boolean(payload.vbot_processing || payload.tts_active || payload.wake_me_up),
    ledBrightness: Math.max(0, Math.min(100, Math.round((Number(payload.led_brightness) || 0) * 100 / 255))),
    musicLocal: Boolean(media.music_local_active),
    podcast: Boolean(media.podcast_active),
    radioSource: Boolean(media.radio_active),
    youtube: Boolean(media.youtube_active),
    zingMp3: Boolean(media.zing_mp3_active),
    nhacCuaTui: Boolean(media.nhaccuatui_active),
    playlistActive: Boolean(media.playlist_active),
    playlistId: String(media.playlist_id || ""),
    playlistName: String(media.playlist_name || ""),
    playlistMode: String(media.playlist_play_mode || ""),
    playlistLoop: Boolean(media.playlist_loop),
    playlistRepeatOne: media.playlist_play_mode === "repeat_one",
    playlistShuffle: media.playlist_play_mode === "random",
    airplayActive: Boolean(media.airplay_active),
    bluetoothActive: Boolean(payload.bluetooth?.active ?? payload.bluetooth_active),
    multiroomActive: Boolean(media.multiroom_active),
    waitingWakeup: Boolean(payload.waiting_wakeup),
    recording: Boolean(payload.recording_active),
    sttProcessing: Boolean(payload.stt_processing),
    assistantProcessing: Boolean(payload.assistant_processing),
    ttsActive: Boolean(payload.tts_active),
    mediaPlaying: state === "playing",
    mqttEnabled: Boolean(payload.mqtt_enabled),
    mqttConnected: Boolean(payload.mqtt_connected),
    multiroomCoordinator: Boolean(media.multiroom_coordinator),
    multiroomClient: Boolean(media.multiroom_active && !media.multiroom_coordinator),
    multiroomPaused: Boolean(media.multiroom_paused),
    multiroomMuted: Boolean(media.multiroom_muted),
    multiroomVolume: clampVolume(media.multiroom_volume),
    multiroomMasterVolume: clampVolume(media.multiroom_master_volume ?? payload.volume),
  };
}

class VBotClient extends EventEmitter {
  constructor(config, fetchImpl = globalThis.fetch) {
    super();
    if (typeof fetchImpl !== "function") throw new Error("Node.js có fetch API là bắt buộc");
    this.config = config;
    this.fetch = fetchImpl;
    this.useDirectControlHttp = fetchImpl === globalThis.fetch;
    this.retryTimer = null;
    this.multiroomRetryTimer = null;
    this.multiroomDiscoveryTimer = null;
    this.streamController = null;
    this.multiroomStreamController = null;
    this.running = false;
    this.lastSignature = "";
    this.commandCooldowns = new Map();
  }

  headers(json = false) {
    const headers = { Accept: "application/json" };
    if (json) headers["Content-Type"] = "application/json";
    if (this.config.apiKey) headers["VBot-API-Key"] = this.config.apiKey;
    return headers;
  }

  async request(path, options = {}) {
    if (this.useDirectControlHttp) return this.requestDirect(path, options);
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), this.config.requestTimeoutMs);
    try {
      const response = await this.fetch(`${this.config.baseUrl}${path}`, {
        ...options,
        headers: { ...this.headers(Boolean(options.body)), ...(options.headers || {}) },
        signal: controller.signal,
      });
      const body = await response.json().catch(() => ({}));
      if (!response.ok || body.success === false) {
        throw new Error(body.message || `VBot API HTTP ${response.status}`);
      }
      return body;
    } finally {
      clearTimeout(timeout);
    }
  }

  requestDirect(path, options = {}) {
    const target = new URL(path, this.config.baseUrl);
    const transport = target.protocol === "https:" ? https : http;
    const body = options.body == null ? null : String(options.body);
    const headers = { ...this.headers(Boolean(body)), ...(options.headers || {}), Connection: "close" };
    if (body) headers["Content-Length"] = Buffer.byteLength(body);
    return new Promise((resolve, reject) => {
      const request = transport.request(target, {
        method: options.method || "GET", headers, agent: false,
      }, response => {
        response.setEncoding("utf8");
        let raw = "";
        response.on("data", chunk => { raw += chunk; });
        response.on("end", () => {
          let payload = {};
          try { payload = raw ? JSON.parse(raw) : {}; }
          catch (_) { return reject(new Error(`VBot API trả về JSON không hợp lệ (HTTP ${response.statusCode})`)); }
          if (response.statusCode < 200 || response.statusCode >= 300 || payload.success === false) {
            return reject(new Error(payload.message || `VBot API HTTP ${response.statusCode}`));
          }
          resolve(payload);
        });
      });
      request.setTimeout(this.config.requestTimeoutMs, () => {
        request.destroy(new Error(`VBot API timeout sau ${this.config.requestTimeoutMs}ms`));
      });
      request.on("error", reject);
      if (body) request.write(body);
      request.end();
    });
  }

  async getState() {
    return normalizeState(await this.request("/?type=1&data=all_info"));
  }

  publishState(payload) {
    const state = normalizeState(payload);
    const signature = JSON.stringify(state);
    if (signature !== this.lastSignature) {
      this.lastSignature = signature;
      this.emit("state", state);
    }
    this.emit("online", true);
  }

  async consumeSse() {
    this.streamController = new AbortController();
    const connectTimeout = setTimeout(() => this.streamController.abort(), this.config.requestTimeoutMs);
    let response;
    try {
      response = await this.fetch(`${this.config.baseUrl}${this.config.ssePath}`, {
        headers: { ...this.headers(), Accept: "text/event-stream" },
        signal: this.streamController.signal,
      });
    } finally {
      clearTimeout(connectTimeout);
    }
    if (!response.ok) throw new Error(`VBot SSE HTTP ${response.status}`);
    if (!response.body) throw new Error("VBot SSE không có response body");
    this.emit("sse", true);

    const decoder = new TextDecoder();
    let buffer = "";
    for await (const chunk of response.body) {
      buffer += decoder.decode(chunk, { stream: true });
      let boundary;
      while ((boundary = buffer.search(/\r?\n\r?\n/)) !== -1) {
        const frame = buffer.slice(0, boundary);
        const separator = buffer.slice(boundary).match(/^\r?\n\r?\n/)[0];
        buffer = buffer.slice(boundary + separator.length);
        const payload = parseSseFrame(frame);
        if (payload) this.publishState(payload);
      }
    }
    if (this.running) throw new Error("Luồng VBot SSE đã đóng");
  }

  async command(type, data, fields = {}) {
    return this.request("/", {
      method: "POST",
      body: JSON.stringify({ type, data, ...fields }),
    });
  }

  coalesceCommand(key, operation, windowMs = 750) {
    const now = Date.now();
    const current = this.commandCooldowns.get(key);
    if (current && current.until > now) return current.promise;
    const promise = Promise.resolve().then(operation);
    const entry = { promise, until: now + windowMs };
    this.commandCooldowns.set(key, entry);
    setTimeout(() => {
      if (this.commandCooldowns.get(key) === entry) this.commandCooldowns.delete(key);
    }, windowMs);
    return promise;
  }

  setVolume(value) {
    return this.command(2, "volume", { action: "setup", value: clampVolume(value) });
  }

  changeVolume(action, source = null, remoteKey = null) {
    if (!new Set(["up", "down", "max", "min"]).has(action)) {
      return Promise.reject(new Error(`Lệnh volume không hợp lệ: ${action}`));
    }
    const payload = { action };
    if (source) payload.source = source;
    if (remoteKey) payload.remote_key = remoteKey;
    return this.command(2, "volume", payload);
  }

  setMute(muted, source = null, remoteKey = null) {
    const payload = { action: muted ? "mute" : "unmute" };
    if (source) payload.source = source;
    if (remoteKey) payload.remote_key = remoteKey;
    return this.command(2, "audio_output", payload);
  }

  mediaAction(action, coalesce = true, source = null, remoteKey = null) {
    if (!new Set(["resume", "pause", "stop", "next", "previous"]).has(action)) {
      return Promise.reject(new Error(`Lệnh media không hợp lệ: ${action}`));
    }
    const payload = { action };
    if (source) payload.source = source;
    if (remoteKey) payload.remote_key = remoteKey;
    const operation = () => this.command(1, "media_control", payload);
    return coalesce ? this.coalesceCommand(`media:${action}`, operation) : operation();
  }

  setFeature(name, enabled) {
    if (!new Set([
      "mic_on_off", "conversation_mode", "wakeup_reply", "wake_up_in_media_player",
      "multiple_command", "continue_listening_after_commands", "cache_tts",
      "media_player_active", "chat_gpt", "google_gemini", "xiaozhi",
      "default_assistant", "olli", "zalo_assistant", "dify_ai", "dev_custom_assistant",
      "music_local", "podcast", "radio", "youtube", "zing_mp3", "nhaccuatui",
    ]).has(name)) {
      return Promise.reject(new Error(`Tính năng VBot không hợp lệ: ${name}`));
    }
    return this.command(2, name, { action: Boolean(enabled) });
  }

  setLedBrightness(value) {
    return this.command(2, "led", { action: "brightness", value: clampVolume(value) });
  }

  wakeUp(source = null, remoteKey = null) {
    const payload = { action: true };
    if (source) payload.source = source;
    if (remoteKey) payload.remote_key = remoteKey;
    return this.command(2, "wake_up", payload);
  }

  runAction(action, coalesce = true, source = null, remoteKey = null) {
    const value = String(action || "").trim();
    if (!/^[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)?$/.test(value) || value === "none") {
      return Promise.reject(new Error(`Action VBot không hợp lệ: ${value}`));
    }
    // Các control thời gian thực không đi qua Button Action Registry. Registry
    // dùng chung async worker với âm báo/nút vật lý nên có thể giữ lệnh HomeKit
    // nhiều giây khi audio bận. API chuyên dụng trả response ngay và tự xử lý
    // feedback âm thanh ở nền.
    const directActions = {
      volume_up: () => this.changeVolume("up", source, remoteKey),
      volume_down: () => this.changeVolume("down", source, remoteKey),
      volume_max: () => this.changeVolume("max", source, remoteKey),
      volume_min: () => this.changeVolume("min", source, remoteKey),
      media_play: () => this.mediaAction("resume", coalesce, source, remoteKey),
      media_pause: () => this.mediaAction("pause", coalesce, source, remoteKey),
      media_stop: () => this.mediaAction("stop", coalesce, source, remoteKey),
      media_next: () => this.mediaAction("next", coalesce, source, remoteKey),
      media_previous: () => this.mediaAction("previous", coalesce, source, remoteKey),
      mute: () => this.setMute(true, source, remoteKey),
      unmute: () => this.setMute(false, source, remoteKey),
      wakeup: () => this.wakeUp(source, remoteKey),
    };
    const payload = { action: value };
    if (source) payload.source = source;
    if (remoteKey) payload.remote_key = remoteKey;
    const operation = directActions[value] || (() => this.command(2, "vbot_action", payload));
    return coalesce ? this.coalesceCommand(`action:${value}`, operation) : operation();
  }

  multiroomAction(action, fields = {}) {
    if (!new Set(["start", "pause", "resume", "stop", "set_mute", "set_volume", "set_master_volume", "add_speakers", "remove_speakers"]).has(action)) {
      return Promise.reject(new Error(`Lệnh Multiroom không hợp lệ: ${action}`));
    }
    return this.request("/multiroom", {
      method: "POST",
      body: JSON.stringify({ action, ...fields }),
    });
  }

  setMultiroomMute(muted) {
    return this.multiroomAction("set_mute", { muted: Boolean(muted) });
  }

  setMultiroomMasterVolume(value) {
    return this.multiroomAction("set_master_volume", { volume: clampVolume(value) });
  }

  setMultiroomSpeakerVolume(speakerId, value) {
    return this.multiroomAction("set_volume", { speaker_ids: [speakerId], volume: clampVolume(value) });
  }

  setMultiroomSpeakerMute(speakerId, muted) {
    return this.multiroomAction("set_mute", { speaker_ids: [speakerId], muted: Boolean(muted) });
  }

  setMultiroomSpeakerJoined(speakerId, joined) {
    return this.multiroomAction(joined ? "add_speakers" : "remove_speakers", { speaker_ids: [speakerId] });
  }

  startMultiroomGroup(groupId) {
    return this.multiroomAction("start", { group_id: String(groupId || "") });
  }

  start() {
    if (this.running) return;
    this.running = true;
    const run = async () => {
      while (this.running) {
      try {
          await this.consumeSse();
      } catch (error) {
        if (!this.running) break;
        this.emit("sse", false);
        this.emit("online", false);
        this.emit("clientError", error);
        // GET chỉ là snapshot dự phòng trong thời gian SSE đang kết nối lại.
        try { this.publishState(await this.request("/?type=1&data=all_info")); } catch (_) {}
        await new Promise(resolve => { this.retryTimer = setTimeout(resolve, this.config.sseReconnectMs); });
      }
      }
    };
    run();
    const runMultiroom = async () => {
      while (this.running) {
        try {
          this.multiroomStreamController = new AbortController();
          const response = await this.fetch(`${this.config.baseUrl}/multiroom/events`, {
            headers: { ...this.headers(), Accept: "text/event-stream" }, signal: this.multiroomStreamController.signal,
          });
          if (!response.ok || !response.body) throw new Error(`Multiroom SSE HTTP ${response.status}`);
          const decoder = new TextDecoder();
          let buffer = "";
          for await (const chunk of response.body) {
            buffer += decoder.decode(chunk, { stream: true });
            let boundary;
            while ((boundary = buffer.search(/\r?\n\r?\n/)) !== -1) {
              const frame = buffer.slice(0, boundary);
              const separator = buffer.slice(boundary).match(/^\r?\n\r?\n/)[0];
              buffer = buffer.slice(boundary + separator.length);
              const payload = parseSseFrame(frame);
              if (payload?.multiroom) this.emit("multiroomState", payload.multiroom);
            }
          }
        } catch (error) {
          if (!this.running) break;
          this.emit("clientError", error);
          await new Promise(resolve => { this.multiroomRetryTimer = setTimeout(resolve, this.config.sseReconnectMs); });
        }
      }
    };
    runMultiroom();
    const discoverMultiroom = async () => {
      if (!this.running) return;
      try {
        const payload = await this.request("/multiroom?discover=true");
        if (payload?.multiroom) this.emit("multiroomState", payload.multiroom);
      } catch (error) {
        if (this.running) this.emit("clientError", error);
      }
    };
    discoverMultiroom();
    this.multiroomDiscoveryTimer = setInterval(discoverMultiroom, 30000);
  }

  stop() {
    this.running = false;
    clearTimeout(this.retryTimer);
    clearTimeout(this.multiroomRetryTimer);
    clearInterval(this.multiroomDiscoveryTimer);
    this.retryTimer = null;
    this.multiroomRetryTimer = null;
    this.multiroomDiscoveryTimer = null;
    this.commandCooldowns.clear();
    if (this.streamController) this.streamController.abort();
    if (this.multiroomStreamController) this.multiroomStreamController.abort();
    this.streamController = null;
    this.multiroomStreamController = null;
  }
}

module.exports = { VBotClient, clampVolume, normalizeState, parseSseFrame };
