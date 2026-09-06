"use strict";

const fs = require("node:fs");
const os = require("node:os");
const path = require("node:path");
const crypto = require("node:crypto");
const { execFileSync } = require("node:child_process");

const HOMEKIT_PERSIST_PATH = "/home/pi/VBot_Node/HomeKit/persist";
const VBOT_SSE_PATH = "/?type=1&data=all_info&stream=sse&interval=1";
const REMOTE_BUTTON_DEFAULTS = Object.freeze({
  arrow_up: "volume_up", arrow_down: "volume_down",
  arrow_left: "media_previous", arrow_right: "media_next",
  select: "wakeup", back: "cancel_wakeup", exit: "media_stop",
  play_pause: "media_play_pause", information: "speak_volume",
  rewind: "media_previous", fast_forward: "media_next",
  next_track: "media_next", previous_track: "media_previous",
  volume_up: "volume_up", volume_down: "volume_down", mute: "mute", power: "media_stop",
});
const REMOTE_BUTTON_STATES = Object.freeze(["playlist", "idle", "media", "media_paused", "tts", "recording"]);

function loadRemoteButtons(homekit, actionControls) {
  const configured = homekit.remote_buttons && typeof homekit.remote_buttons === "object"
    ? homekit.remote_buttons : {};
  const allowed = new Set(["none", ...actionControls.map(item => item.id)]);
  return Object.fromEntries(Object.entries(REMOTE_BUTTON_DEFAULTS).map(([key, fallback]) => {
    const supplied = configured[key];
    const value = supplied && typeof supplied === "object" ? supplied : { action: supplied };
    const defaultAction = String(value.action ?? fallback).trim();
    const action = allowed.has(defaultAction) ? defaultAction : fallback;
    const suppliedStates = value.actions_by_state && typeof value.actions_by_state === "object"
      ? value.actions_by_state : {};
    const actionsByState = Object.fromEntries(REMOTE_BUTTON_STATES.map(state => {
      const stateAction = String(suppliedStates[state] ?? action).trim();
      return [state, allowed.has(stateAction) ? stateAction : action];
    }));
    return [key, { action, actionsByState }];
  }));
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, "utf8").replace(/^\uFEFF/, ""));
}

function raspberryPiOtpSerial(executor = execFileSync) {
  try {
    // Tương đương vcgencmd otp_dump | grep '^28:' nhưng không qua shell.
    const output = executor("vcgencmd", ["otp_dump"], {
      encoding: "utf8", timeout: 3000, stdio: ["ignore", "pipe", "ignore"],
    });
    const match = String(output || "").match(/^28:([0-9a-f]+)\s*$/im);
    return match ? match[1].toUpperCase() : "";
  } catch (_) {
    return "";
  }
}

function fallbackHardwareIdentity() {
  for (const interfaces of Object.values(os.networkInterfaces() || {})) {
    for (const item of interfaces || []) {
      const mac = String(item.mac || "").toUpperCase();
      if (!item.internal && /^([0-9A-F]{2}:){5}[0-9A-F]{2}$/.test(mac)
          && mac !== "00:00:00:00:00:00") return `MAC:${mac}`;
    }
  }
  return `HOST:${os.hostname()}`;
}

function autoUsername(name, identity = null) {
  identity = String(identity || raspberryPiOtpSerial() || fallbackHardwareIdentity());
  const bytes = crypto.createHash("sha256").update(`${identity}:${name}`).digest().subarray(0, 5);
  return [0x02, ...bytes].map(value => value.toString(16).padStart(2, "0").toUpperCase()).join(":");
}

function sanitizeHomeKitName(value) {
  return String(value || "")
    .normalize("NFC")
    .replace(/[^\p{L}\p{N} '.,_-]/gu, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function loadActionControls(vbotConfig, vbotConfigPath) {
  const root = path.dirname(vbotConfigPath);
  const controls = [];
  const seen = new Set();
  const append = (id, label) => {
    id = String(id || "").trim();
    label = sanitizeHomeKitName(label || id);
    if (!id || id === "none" || !label || seen.has(id)) return;
    if (!/^[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)?$/.test(id)) return;
    seen.add(id);
    controls.push({ id, label });
  };

  try {
    const registry = readJson(path.join(root, "resource", "action_registry.json"));
    for (const item of registry.actions || []) append(item.id, item.label);
  } catch (_) {}

  try {
    const manifest = readJson(path.join(root, "html", "includes", "cache", "PlayLists.json"));
    for (const playlist of manifest.playlists || []) {
      const id = String(playlist.id || "").trim();
      if (/^[A-Za-z0-9_-]{1,64}$/.test(id)) append(`playlist:${id}`, `Phát Playlist ${playlist.name || id}`);
    }
  } catch (_) {}
  append("playlist:default", "Phát Playlist Mặc định");

  for (const radio of (((vbotConfig || {}).media_player || {}).radio_data || [])) {
    const name = String(radio.name || "").trim();
    const link = String(radio.link || "").trim();
    if (!name || !link) continue;
    const id = crypto.createHash("sha1").update(`${name}\n${link}`).digest("hex").slice(0, 12);
    append(`radio:${id}`, `Phát Radio ${name}`);
  }
  return controls;
}

function loadHomeKitRegistry(vbotConfigPath) {
  const registryPath = path.join(path.dirname(vbotConfigPath), "resource", "HomeKit", "homekit_registry.json");
  const registry = readJson(registryPath);
  if (!registry || !Array.isArray(registry.controls)) throw new Error(`homekit_registry.json không hợp lệ: ${registryPath}`);
  const ids = new Set();
  const items = [...registry.controls];
  const groupPath = path.join(path.dirname(vbotConfigPath), "resource", "multiroom_audio", "multiroom_groups.json");
  const multiroom = fs.existsSync(groupPath) ? (readJson(groupPath) || {}) : {};
  for (const [groupId, group] of Object.entries(multiroom.groups || {})) {
    items.push({
      id: `multiroom_group_${String(groupId).replace(/[^a-z0-9_]/gi, "_").toLowerCase()}`,
      name: `Phát Multiroom ${group.name || groupId}`,
      group: "multiroom", service: "button", api_method: "multiroom_group", group_id: groupId,
    });
  }
  return items.map(item => {
    const control = {
      id: String(item.id || "").trim(), name: sanitizeHomeKitName(item.name),
      group: String(item.group || "").trim(), service: String(item.service || "").trim(),
      stateKey: String(item.state_key || "").trim(), apiName: String(item.api_name || "").trim(),
      apiMethod: String(item.api_method || "feature").trim(), apiAction: String(item.api_action || "").trim(),
      groupId: String(item.group_id || "").trim(),
    };
    if (!/^[a-z0-9_]+$/.test(control.id) || ids.has(control.id)) throw new Error(`HomeKit control id không hợp lệ hoặc trùng: ${control.id}`);
    if (!control.name || !["media", "multiroom", "voice", "system", "assistants", "status"].includes(control.group)) throw new Error(`HomeKit control không hợp lệ: ${control.id}`);
    if (!["switch", "brightness", "volume", "percentage", "button", "occupancy"].includes(control.service)) throw new Error(`HomeKit service không hỗ trợ: ${control.id}`);
    if (control.service !== "button" && !control.stateKey) throw new Error(`HomeKit control thiếu state_key: ${control.id}`);
    if (["switch", "brightness"].includes(control.service) && control.apiMethod === "feature" && !control.apiName) throw new Error(`HomeKit control thiếu api_name: ${control.id}`);
    if (control.apiMethod === "multiroom_group" && !control.groupId) throw new Error(`HomeKit control thiếu group_id: ${control.id}`);
    ids.add(control.id);
    return control;
  });
}

function loadMultiroomSpeakers(vbotConfigPath) {
  const groupPath = path.join(path.dirname(vbotConfigPath), "resource", "multiroom_audio", "multiroom_groups.json");
  if (!fs.existsSync(groupPath)) return [];
  const data = readJson(groupPath) || {};
  const speakers = new Map();
  for (const group of Object.values(data.groups || {})) {
    for (const member of group.members || []) {
      const id = String(typeof member === "object" ? member.id : member || "").trim().toLowerCase();
      if (!id || speakers.has(id)) continue;
      const suppliedName = typeof member === "object" ? member.name : "";
      speakers.set(id, { id, name: sanitizeHomeKitName(suppliedName || `Loa ${id.slice(-6).toUpperCase()}`) });
    }
  }
  return [...speakers.values()];
}

function buildConfig(vbotConfig, vbotConfigPath, suppliedVersionConfig = null) {
  const api = vbotConfig.api || {};
  const port = Number(api.port);
  if (!Number.isInteger(port) || port < 1 || port > 65535) {
    throw new Error(`api.port trong ${vbotConfigPath} không hợp lệ`);
  }
  const homekit = vbotConfig.homekit;
  if (!homekit || typeof homekit !== "object") {
    throw new Error(`Thiếu mục homekit trong ${vbotConfigPath}`);
  }
  const name = sanitizeHomeKitName(vbotConfig.contact_info && vbotConfig.contact_info.full_name);
  if (!name) throw new Error(`contact_info.full_name trong ${vbotConfigPath} đang trống`);
  const versionPath = path.join(path.dirname(vbotConfigPath), "Version.json");
  const versionConfig = suppliedVersionConfig || readJson(versionPath);
  const firmwareRevision = String(versionConfig.version || "").trim();
  if (!firmwareRevision) throw new Error(`version trong ${versionPath} đang trống`);
  const apiKey = api.auth && api.auth.active ? String(api.auth.api_key || "") : "";
  if (api.auth && api.auth.active && !apiKey) {
    throw new Error(`API VBot bật xác thực nhưng api.auth.api_key trong ${vbotConfigPath} đang trống`);
  }
  const accessoryType = String(homekit.accessory_type || "hybrid").trim().toLowerCase();
  if (!["speaker", "television", "switches", "hybrid"].includes(accessoryType)) {
    throw new Error(`homekit.accessory_type không hợp lệ: ${accessoryType}`);
  }
  const actionControls = loadActionControls(vbotConfig, vbotConfigPath);
  return {
    active: homekit.active === true,
    accessoryType,
    name,
    manufacturer: homekit.manufacturer,
    model: homekit.model,
    serialNumber: homekit.serial_number,
    firmwareRevision,
    pincode: homekit.pincode,
    username: homekit.username,
    port: Number(homekit.port),
    persistPath: HOMEKIT_PERSIST_PATH,
    actionControls,
    remoteButtons: loadRemoteButtons(homekit, actionControls),
    registryControls: suppliedVersionConfig ? [] : loadHomeKitRegistry(vbotConfigPath),
    multiroomSpeakers: suppliedVersionConfig ? [] : loadMultiroomSpeakers(vbotConfigPath),
    vbot: {
      baseUrl: `http://127.0.0.1:${port}`,
      apiKey,
      requestTimeoutMs: Math.max(500, Number(homekit.request_timeout_ms || 4000)),
      ssePath: VBOT_SSE_PATH,
      sseReconnectMs: Math.max(500, Number(homekit.sse_reconnect_ms || 3000)),
    },
    configPath: vbotConfigPath,
  };
}

function loadConfig(configPath) {
  if (!fs.existsSync(configPath)) throw new Error(`Không tìm thấy Config.json của VBot: ${configPath}`);
  const config = buildConfig(readJson(configPath), configPath);
  // OTP hàng 28 là định danh phần cứng, không bị clone theo OS image như
  // /etc/machine-id. Serial cấu hình chỉ làm fallback khi thiếu vcgencmd.
  const otpSerial = raspberryPiOtpSerial();
  const hardwareIdentity = otpSerial || fallbackHardwareIdentity();
  if (otpSerial) config.serialNumber = `28:${otpSerial}`;
  config.setupID = crypto.createHash("sha256").update(`VBot:${hardwareIdentity}`).digest("hex").slice(0, 4).toUpperCase();
  if (!config.username || config.username === "auto") config.username = autoUsername("VBot Assistant", hardwareIdentity);
  if (!/^\d{3}-\d{2}-\d{3}$/.test(config.pincode)) throw new Error("HomeKit pincode phải có dạng 031-45-154");
  if (!/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/i.test(config.username)) throw new Error("HomeKit username phải là địa chỉ MAC 6 byte");
  if (!Number.isInteger(config.port) || config.port < 1 || config.port > 65535) throw new Error("homekit.port không hợp lệ");
  return config;
}

module.exports = { HOMEKIT_PERSIST_PATH, VBOT_SSE_PATH, REMOTE_BUTTON_DEFAULTS, REMOTE_BUTTON_STATES, raspberryPiOtpSerial, fallbackHardwareIdentity, autoUsername, buildConfig, loadActionControls, loadHomeKitRegistry, loadRemoteButtons, loadConfig, sanitizeHomeKitName };
