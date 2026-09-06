#!/usr/bin/env node
"use strict";

const crypto = require("node:crypto");
const fs = require("node:fs");
const path = require("node:path");

const VBOT_ERROR_LOG = path.resolve(__dirname, "..", "log", "Vbot_error.log");
const VBOT_SERVICE_LOG = path.resolve(__dirname, "..", "log", "service_log.log");
const HOMEKIT_LOG_MAX_BYTES = 2 * 1024 * 1024;
const homekitLogSizes = new Map();
const originalConsoleLog = console.log.bind(console);
const originalConsoleWarn = console.warn.bind(console);
const originalConsoleError = console.error.bind(console);

function errorText(value) {
  if (value instanceof Error) return value.stack || value.message;
  if (typeof value === "string") return value;
  try { return JSON.stringify(value); } catch (_) { return String(value); }
}

function runtimeTimestamp() {
  return new Date().toISOString();
}

function appendBoundedLog(filePath, line, callback = null) {
  try {
    let size = homekitLogSizes.get(filePath);
    if (size == null) {
      try { size = fs.statSync(filePath).size; } catch (_) { size = 0; }
    }
    const lineBytes = Buffer.byteLength(line);
    if (size + lineBytes > HOMEKIT_LOG_MAX_BYTES) {
      const rotatedPath = `${filePath}.1`;
      try { fs.rmSync(rotatedPath, { force: true }); } catch (_) {}
      try { fs.renameSync(filePath, rotatedPath); } catch (error) {
        if (error.code !== "ENOENT") throw error;
      }
      size = 0;
    }
    homekitLogSizes.set(filePath, size + lineBytes);
    fs.appendFile(filePath, line, "utf8", callback || (() => {}));
  } catch (error) {
    if (callback) callback(error);
    else originalConsoleError("[VBot_HomeKit_Node] Không thể ghi log giới hạn: ", error);
  }
}

function appendVBotService(level, values) {
  try {
    fs.mkdirSync(path.dirname(VBOT_SERVICE_LOG), { recursive: true });
    const message = values.map(errorText).join(" ").replace(/^\[VBot_HomeKit_Node\]\s*/, "");
    const line = `[${runtimeTimestamp()}] [VBot_HomeKit_Node] [${level}] ${message}\n`;
    // Không chặn event loop/HomeKit trong lúc ghi log chẩn đoán.
    appendBoundedLog(VBOT_SERVICE_LOG, line, error => {
      if (error) originalConsoleError("[VBot_HomeKit_Node] Không thể ghi service_log.log: ", error);
    });
  } catch (logError) {
    originalConsoleError("[VBot_HomeKit_Node] Không thể chuẩn bị service_log.log: ", logError);
  }
}

function appendVBotError(...values) {
  try {
    fs.mkdirSync(path.dirname(VBOT_ERROR_LOG), { recursive: true });
    const timestamp = new Date().toLocaleString("vi-VN", { hour12: false, timeZone: "Asia/Ho_Chi_Minh" });
    const message = values.map(errorText).join(" ").replace(/^\[VBot_HomeKit_Node\]\s*/, "");
    appendBoundedLog(VBOT_ERROR_LOG, `[${timestamp}] [VBot_HomeKit_Node] ${message}\n`, error => {
      if (error) originalConsoleError("[VBot_HomeKit_Node] Không thể ghi Vbot_error.log: ", error);
    });
  } catch (logError) {
    originalConsoleError("[VBot_HomeKit_Node] Không thể ghi Vbot_error.log: ", logError);
  }
}

console.log = (...values) => {
  //appendVBotService("INFO", values);
  originalConsoleLog(...values);
};
console.warn = (...values) => {
  appendVBotService("WARN", values);
  originalConsoleWarn(...values);
};
console.error = (...values) => {
  appendVBotService("ERROR", values);
  appendVBotError(...values);
  originalConsoleError(...values);
};
process.on("uncaughtException", error => {
  appendVBotError("uncaughtException:", error);
  originalConsoleError("[VBot_HomeKit_Node] uncaughtException:", error);
  process.exit(1);
});
process.on("unhandledRejection", reason => {
  appendVBotError("unhandledRejection:", reason);
  originalConsoleError("[VBot_HomeKit_Node] unhandledRejection:", reason);
});

const QRCode = require("qrcode");
const { Accessory, Bridge, Categories, Characteristic, HAPStorage, Service, uuid } = require("@homebridge/hap-nodejs");
const { loadConfig } = require("./lib/config");
const { VBotClient } = require("./lib/vbot-client");

const configPath = process.env.VBOT_CONFIG || "/home/pi/VBot_Offline/Config.json";
const config = loadConfig(configPath);
if (!config.active) {
  console.log("[VBot_HomeKit_Node] HomeKit không được kích hoạt trong Config.json của chương trình VBot");
  process.exit(0);
}
HAPStorage.setCustomStoragePath(config.persistPath);

function configureInformation(accessory, model, suffix = "") {
  accessory.getService(Service.AccessoryInformation)
    .setCharacteristic(Characteristic.Manufacturer, config.manufacturer)
    .setCharacteristic(Characteristic.Model, model)
    .setCharacteristic(Characteristic.SerialNumber, `${config.serialNumber}${suffix}`)
    .setCharacteristic(Characteristic.FirmwareRevision, config.firmwareRevision);
}

function setControlName(service, name) {
  service.setCharacteristic(Characteristic.Name, name);
  if (!service.testCharacteristic(Characteristic.ConfiguredName)) service.addCharacteristic(Characteristic.ConfiguredName);
  service.setCharacteristic(Characteristic.ConfiguredName, name);
  return service;
}

// HomeKit hoàn tất transaction On=true sau khi onSet trả về. Reset ngay trong
// onSet có thể bị giá trị true cuối transaction ghi đè, nên nút momentary phải
// phát một update Off trễ để ứng dụng Nhà nhận được cạnh trạng thái rõ ràng.
const momentaryResetTimers = new Map();
const sliderWriteTimers = new Map();
const SLIDER_DEBOUNCE_MS = 250;
function resetMomentaryControl(control, delayMs = 350) {
  const previous = momentaryResetTimers.get(control);
  if (previous) clearTimeout(previous);
  const timer = setTimeout(() => {
    momentaryResetTimers.delete(control);
    control.updateCharacteristic(Characteristic.On, false);
  }, delayMs);
  momentaryResetTimers.set(control, timer);
}

// Apple Home phát nhiều onSet trong lúc kéo và không có event "touch end".
// Debounce cạnh cuối mô phỏng thao tác nhả tay: chỉ giá trị cuối sau một quãng
// yên lặng mới được gửi tới API VBot.
function debounceSliderControl(key, rawValue, operation, delayMs = SLIDER_DEBOUNCE_MS) {
  const value = Math.max(0, Math.min(100, Math.round(Number(rawValue) || 0)));
  let entry = sliderWriteTimers.get(key);
  if (entry) {
    entry.value = value;
    entry.operation = operation;
    entry.version += 1;
    if (!entry.running) {
      clearTimeout(entry.timer);
      entry.timer = setTimeout(() => flushSliderControl(key, entry, delayMs), delayMs);
    }
    return Promise.resolve();
  }
  entry = { value, operation, version: 1, running: false, timer: null };
  entry.timer = setTimeout(() => flushSliderControl(key, entry, delayMs), delayMs);
  sliderWriteTimers.set(key, entry);
  // Không giữ transaction HAP mở trong thời gian debounce.
  return Promise.resolve();
}

async function flushSliderControl(key, entry, delayMs) {
  if (sliderWriteTimers.get(key) !== entry || entry.running) return;
  entry.running = true;
  const sentVersion = entry.version;
  const value = entry.value;
  const operation = entry.operation;
  try {
    await operation(value);
  } catch (_) {
    // runControl đã ghi log chi tiết; không để Promise của timer thành
    // unhandled rejection làm bridge mất ổn định.
  } finally {
    entry.running = false;
    if (sliderWriteTimers.get(key) !== entry) return;
    if (entry.version !== sentVersion) {
      // Trong lúc HTTP đang chạy chỉ giữ lại đúng giá trị mới nhất.
      entry.timer = setTimeout(() => flushSliderControl(key, entry, delayMs), delayMs);
    } else {
      sliderWriteTimers.delete(key);
    }
  }
}

function sliderDisplayValue(key, fallback) {
  const pending = sliderWriteTimers.get(key);
  return pending ? pending.value : Math.max(0, Math.min(100, Number(fallback || 0)));
}

function createChild(key, name, model, category = Categories.SWITCH) {
  const child = new Accessory(name, uuid.generate(`vbot:homekit:${config.serialNumber}:${key}`));
  child.category = category;
  configureInformation(child, model, `-${key}`);
  child.on("identify", (paired, callback) => {
    console.log(`[VBot_HomeKit_Node] Nhận diện ${name} (paired=${paired})`);
    callback();
  });
  return child;
}

const bridgeName = `${config.name} Bridge`;
// Giữ UUID và username HAP cũ để dữ liệu pairing vẫn có cơ hội được Home cập nhật.
const bridge = new Bridge(bridgeName, uuid.generate(`vbot:homekit:${config.serialNumber}`));
configureInformation(bridge, `${config.model} Bridge`);

const groups = {
  speaker: createChild("speaker", config.name, config.model, Categories.SPEAKER),
  remote: createChild("remote", "VBot Remote", "VBot Media Remote", Categories.TELEVISION),
  media: createChild("media", "VBot Media", "VBot Media Controls"),
  multiroom: createChild("multiroom", "VBot Multiroom", "VBot Multiroom Controls", Categories.SPEAKER),
  voice: createChild("voice", "VBot Giọng nói", "VBot Voice Controls"),
  playlists: createChild("playlists", "VBot Playlist", "VBot Playlist Controls"),
  radio: createChild("radio", "VBot Radio", "VBot Radio Controls"),
  system: createChild("system", "VBot Hệ thống", "VBot System Controls"),
  assistants: createChild("assistants", "VBot Trợ lý AI", "VBot Assistant Controls"),
  status: createChild("status", "VBot Trạng thái", "VBot Status Sensors", Categories.SENSOR),
};
// Accessory độc lập để Home hiển thị một tile Đánh Thức VBot riêng, không bị
// gom vào các nhóm Giọng nói, Media hoặc Hệ thống.
const wakeupAccessory = createChild("wakeup", "Đánh Thức VBot", "VBot Wakeup Button", Categories.SWITCH);

const useSpeaker = ["speaker", "hybrid"].includes(config.accessoryType);
const useTelevision = ["television", "hybrid"].includes(config.accessoryType);
let speaker = null;
let television = null;
let televisionSpeaker = null;
if (useSpeaker) {
  speaker = new Service.SmartSpeaker(config.name, "vbot-speaker");
  speaker.setPrimaryService();
  speaker.setCharacteristic(Characteristic.ConfiguredName, config.name);
  speaker.setCharacteristic(Characteristic.Volume, 0);
  speaker.setCharacteristic(Characteristic.Mute, false);
  speaker.setCharacteristic(Characteristic.CurrentMediaState, Characteristic.CurrentMediaState.STOP);
  speaker.setCharacteristic(Characteristic.TargetMediaState, Characteristic.TargetMediaState.STOP);
  groups.speaker.addService(speaker);
}
if (useTelevision) {
  television = setControlName(new Service.Television("VBot Remote", "vbot-television"), "VBot Remote");
  television.setPrimaryService();
  // Remote là giao diện điều khiển luôn sẵn sàng khi bridge online. Không gắn
  // Active với trạng thái media, vì iPhone gửi Active=ON kèm nhiều phím Remote.
  television.setCharacteristic(Characteristic.Active, Characteristic.Active.ACTIVE);
  television.setCharacteristic(Characteristic.ActiveIdentifier, 1);
  television.setCharacteristic(Characteristic.SleepDiscoveryMode, Characteristic.SleepDiscoveryMode.ALWAYS_DISCOVERABLE);
  const input = setControlName(new Service.InputSource("VBot Media", "vbot-television-input"), "VBot Media");
  input.setCharacteristic(Characteristic.Identifier, 1);
  input.setCharacteristic(Characteristic.InputSourceType, Characteristic.InputSourceType.APPLICATION);
  input.setCharacteristic(Characteristic.IsConfigured, Characteristic.IsConfigured.CONFIGURED);
  input.setCharacteristic(Characteristic.CurrentVisibilityState, Characteristic.CurrentVisibilityState.SHOWN);
  groups.remote.addService(television);
  groups.remote.addService(input);
  televisionSpeaker = setControlName(new Service.TelevisionSpeaker("VBot Volume", "vbot-television-speaker"), "VBot Volume");
  televisionSpeaker.setCharacteristic(Characteristic.Active, Characteristic.Active.ACTIVE);
  televisionSpeaker.setCharacteristic(Characteristic.Mute, false);
  // Apple Remote phat VolumeSelector theo kieu tang/giam tuong doi. Khai bao
  // ABSOLUTE trong khi service khong co characteristic Volume co the lam iOS
  // khong gui (hoac vo hieu hoa) nut mute cua TelevisionSpeaker.
  televisionSpeaker.setCharacteristic(Characteristic.VolumeControlType, Characteristic.VolumeControlType.RELATIVE);
  groups.remote.addService(televisionSpeaker);
  television.addLinkedService(input);
  television.addLinkedService(televisionSpeaker);
}

const playbackControl = setControlName(new Service.Switch("Phát Tạm dừng", "vbot-playback-control"), "Phát Tạm dừng");
const muteControl = setControlName(new Service.Switch("Tắt tiếng", "vbot-mute-control"), "Tắt tiếng");
const volumeControl = config.accessoryType === "switches" ? setControlName(new Service.Lightbulb("Âm lượng dự phòng", "vbot-volume-control"), "Âm lượng dự phòng") : null;
playbackControl.setPrimaryService();
playbackControl.setCharacteristic(Characteristic.On, false);
muteControl.setCharacteristic(Characteristic.On, false);
groups.media.addService(playbackControl);
groups.media.addService(muteControl);
if (volumeControl) {
  volumeControl.addOptionalCharacteristic(Characteristic.Brightness);
  volumeControl.setCharacteristic(Characteristic.On, true);
  volumeControl.setCharacteristic(Characteristic.Brightness, 0);
  groups.media.addService(volumeControl);
}

const wakeupControl = setControlName(new Service.Switch("Đánh thức VBot", "vbot-wakeup-control"), "Đánh thức VBot");
const micControl = setControlName(new Service.Switch("Micro", "vbot-mic-control"), "Micro");
const conversationControl = setControlName(new Service.Switch("Chế độ hội thoại", "vbot-conversation-control"), "Chế độ hội thoại");
wakeupControl.setPrimaryService();
wakeupControl.setCharacteristic(Characteristic.On, false);
micControl.setCharacteristic(Characteristic.On, true);
conversationControl.setCharacteristic(Characteristic.On, false);
wakeupAccessory.addService(wakeupControl);
groups.voice.addService(micControl);
groups.voice.addService(conversationControl);

const stateSwitches = [];
function addStateSwitch(group, name, subtype, stateKey, apiName) {
  const control = setControlName(new Service.Switch(name, subtype), name);
  control.setCharacteristic(Characteristic.On, false);
  control.getCharacteristic(Characteristic.On)
    .onGet(() => Boolean(latest[stateKey]))
    .onSet(value => dispatchControl(
      `Yêu cầu ${value ? "bật" : "tắt"} ${name}`,
      () => client.setFeature(apiName, Boolean(value)),
    ));
  group.addService(control);
  stateSwitches.push({ control, stateKey });
  return control;
}

addStateSwitch(groups.voice, "Câu phản hồi Wakeup", "vbot-wakeup-reply", "wakeupReply", "wakeup_reply");
addStateSwitch(groups.voice, "Đánh thức khi phát media", "vbot-wake-in-media", "wakeInMedia", "wake_up_in_media_player");
addStateSwitch(groups.voice, "Chế độ nhiều câu lệnh", "vbot-multiple-command", "multipleCommand", "multiple_command");
addStateSwitch(groups.voice, "Tiếp tục nghe sau câu lệnh", "vbot-continue-listening", "continueListening", "continue_listening_after_commands");
addStateSwitch(groups.voice, "Cache TTS", "vbot-cache-tts", "cacheTts", "cache_tts");
addStateSwitch(groups.media, "Media Player", "vbot-media-player-active", "mediaPlayerActive", "media_player_active");

addStateSwitch(groups.assistants, "ChatGPT", "vbot-chat-gpt", "chatGpt", "chat_gpt").setPrimaryService();
addStateSwitch(groups.assistants, "Google Gemini", "vbot-google-gemini", "googleGemini", "google_gemini");
addStateSwitch(groups.assistants, "Xiaozhi", "vbot-xiaozhi", "xiaozhi", "xiaozhi");
addStateSwitch(groups.assistants, "Trợ lý mặc định", "vbot-default-assistant", "defaultAssistant", "default_assistant");
addStateSwitch(groups.assistants, "Olli", "vbot-olli", "olli", "olli");
addStateSwitch(groups.assistants, "Zalo Assistant", "vbot-zalo-assistant", "zaloAssistant", "zalo_assistant");
addStateSwitch(groups.assistants, "Dify AI", "vbot-dify-ai", "difyAi", "dify_ai");
addStateSwitch(groups.assistants, "Trợ lý tùy chỉnh", "vbot-dev-assistant", "devAssistant", "dev_custom_assistant");

const onlineSensor = setControlName(new Service.OccupancySensor("VBot trực tuyến", "vbot-online"), "VBot trực tuyến");
const processingSensor = setControlName(new Service.OccupancySensor("VBot đang hoạt động", "vbot-processing"), "VBot đang hoạt động");
onlineSensor.setPrimaryService();
onlineSensor.setCharacteristic(Characteristic.OccupancyDetected, Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED);
processingSensor.setCharacteristic(Characteristic.OccupancyDetected, Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED);
groups.status.addService(onlineSensor);
groups.status.addService(processingSensor);

// Các control mở rộng được dựng từ homekit_registry.json để thêm tính năng mới
// mà không phải tiếp tục hard-code cấu trúc service trong bridge.
const registryStateControls = [];
for (const definition of config.registryControls) {
  const group = groups[definition.group];
  const subtype = `vbot-registry-${definition.id}`;
  if (definition.service === "switch") {
    const control = setControlName(new Service.Switch(definition.name, subtype), definition.name);
    control.setCharacteristic(Characteristic.On, false);
    control.getCharacteristic(Characteristic.On)
      .onGet(() => Boolean(latest[definition.stateKey]))
      .onSet(value => dispatchControl(
        `Yêu cầu ${value ? "bật" : "tắt"} ${definition.name}`,
        () => definition.apiMethod === "multiroom_mute"
          ? client.setMultiroomMute(Boolean(value))
          : client.setFeature(definition.apiName, Boolean(value)),
      ));
    group.addService(control);
    registryStateControls.push({ control, definition, characteristic: Characteristic.On });
  } else if (definition.service === "brightness" || definition.service === "volume") {
    const sliderKey = definition.apiMethod === "local_volume" ? "vbot-local-volume" : definition.id;
    const control = setControlName(new Service.Lightbulb(definition.name, subtype), definition.name);
    control.addOptionalCharacteristic(Characteristic.Brightness);
    control.setCharacteristic(Characteristic.On, true);
    control.setCharacteristic(Characteristic.Brightness, 0);
    control.getCharacteristic(Characteristic.On).onGet(() => sliderDisplayValue(sliderKey, latest[definition.stateKey]) > 0).onSet(value => {
      if (value || Number(latest[definition.stateKey] || 0) === 0) return Promise.resolve();
      return debounceSliderControl(sliderKey, 0, finalValue =>
        runControl(`Yêu cầu ${definition.name} ${finalValue}%`, () => {
          if (definition.apiMethod === "local_volume") return client.setVolume(finalValue);
          if (definition.service === "volume") return client.setMultiroomMasterVolume(finalValue);
          return client.setLedBrightness(finalValue);
        }));
    });
    control.getCharacteristic(Characteristic.Brightness)
      .onGet(() => sliderDisplayValue(sliderKey, latest[definition.stateKey]))
      .onSet(value => debounceSliderControl(sliderKey, value, finalValue =>
        runControl(`Yêu cầu ${definition.name} ${finalValue}%`, () => {
          if (definition.apiMethod === "local_volume") return client.setVolume(finalValue);
          if (definition.service === "volume") return client.setMultiroomMasterVolume(finalValue);
          return client.setLedBrightness(finalValue);
        })));
    group.addService(control);
    registryStateControls.push({ control, definition, characteristic: Characteristic.Brightness });
  } else if (definition.service === "button") {
    const control = setControlName(new Service.Switch(definition.name, subtype), definition.name);
    control.setCharacteristic(Characteristic.On, false);
    control.getCharacteristic(Characteristic.On).onGet(() => false).onSet(value => {
      if (!value) return Promise.resolve();
      let command;
      if (definition.apiMethod === "multiroom_group") command = () => client.startMultiroomGroup(definition.groupId);
      else if (definition.apiMethod === "action") {
        resetMomentaryControl(control);
        return dispatchConfiguredAction(`Yêu cầu ${definition.name}`, definition.apiAction);
      }
      else command = () => client.multiroomAction(definition.apiAction);
      resetMomentaryControl(control);
      return dispatchControl(`Yêu cầu ${definition.name}`, command);
    });
    group.addService(control);
  } else if (definition.service === "percentage") {
    // HomeKit không có cảm biến phần trăm chung. HumiditySensor cung cấp một
    // characteristic chuẩn, chỉ đọc, có miền 0-100% và cập nhật tốt qua SSE.
    const control = setControlName(new Service.HumiditySensor(definition.name, subtype), definition.name);
    control.setCharacteristic(Characteristic.CurrentRelativeHumidity, 0);
    control.getCharacteristic(Characteristic.CurrentRelativeHumidity)
      .onGet(() => Math.max(0, Math.min(100, Number(latest[definition.stateKey] || 0))));
    group.addService(control);
    registryStateControls.push({ control, definition, characteristic: Characteristic.CurrentRelativeHumidity });
  } else if (definition.service === "occupancy") {
    const control = setControlName(new Service.OccupancySensor(definition.name, subtype), definition.name);
    control.setCharacteristic(Characteristic.OccupancyDetected, Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED);
    group.addService(control);
    registryStateControls.push({ control, definition, characteristic: Characteristic.OccupancyDetected });
  }
}

const multiroomSpeakerControls = new Map();
for (const definition of config.multiroomSpeakers) {
  const subtype = crypto.createHash("sha1").update(definition.id).digest("hex").slice(0, 12);
  const joined = setControlName(new Service.Switch(`${definition.name} trong nhóm`, `multiroom-joined-${subtype}`), `${definition.name} trong nhóm`);
  const muted = setControlName(new Service.Switch(`${definition.name} tắt tiếng`, `multiroom-muted-${subtype}`), `${definition.name} tắt tiếng`);
  const volume = setControlName(new Service.Lightbulb(`${definition.name} âm lượng`, `multiroom-volume-${subtype}`), `${definition.name} âm lượng`);
  const online = setControlName(new Service.OccupancySensor(`${definition.name} trực tuyến`, `multiroom-online-${subtype}`), `${definition.name} trực tuyến`);
  volume.addOptionalCharacteristic(Characteristic.Brightness);
  volume.setCharacteristic(Characteristic.On, true).setCharacteristic(Characteristic.Brightness, 0);
  joined.setCharacteristic(Characteristic.On, false).getCharacteristic(Characteristic.On)
    .onGet(() => Boolean(multiroomSpeakerControls.get(definition.id)?.state.joined))
    .onSet(value => dispatchControl(`Yêu cầu ${value ? "thêm" : "xóa"} ${definition.name}`, () => client.setMultiroomSpeakerJoined(definition.id, Boolean(value))));
  muted.setCharacteristic(Characteristic.On, false).getCharacteristic(Characteristic.On)
    .onGet(() => Boolean(multiroomSpeakerControls.get(definition.id)?.state.muted))
    .onSet(value => dispatchControl(`Yêu cầu mute ${definition.name}`, () => client.setMultiroomSpeakerMute(definition.id, Boolean(value))));
  volume.getCharacteristic(Characteristic.On).onGet(() => Number(multiroomSpeakerControls.get(definition.id)?.state.volume || 0) > 0);
  volume.getCharacteristic(Characteristic.Brightness)
    .onGet(() => Number(multiroomSpeakerControls.get(definition.id)?.state.volume || 0))
    .onSet(value => debounceSliderControl(`multiroom-speaker-${definition.id}`, value, finalValue =>
      runControl(`Yêu cầu âm lượng ${definition.name} ${finalValue}%`, () => client.setMultiroomSpeakerVolume(definition.id, finalValue))));
  groups.multiroom.addService(joined);
  groups.multiroom.addService(muted);
  groups.multiroom.addService(volume);
  groups.multiroom.addService(online);
  multiroomSpeakerControls.set(definition.id, { joined, muted, volume, online, state: { joined: false, muted: false, volume: 0, online: false } });
}

function addMomentaryMediaButton(group, name, subtype, action) {
  const control = setControlName(new Service.Switch(name, subtype), name);
  control.setCharacteristic(Characteristic.On, false);
  control.getCharacteristic(Characteristic.On).onGet(() => false).onSet(value => {
    if (!value) return Promise.resolve();
    resetMomentaryControl(control);
    return dispatchControl(`Yêu cầu ${name}`, () => client.mediaAction(action, false));
  });
  group.addService(control);
  return control;
}

const playlistActiveSensor = setControlName(new Service.OccupancySensor("Playlist đang phát", "playlist-active"), "Playlist đang phát");
const radioActiveSensor = setControlName(new Service.OccupancySensor("Radio đang phát", "radio-active"), "Radio đang phát");
groups.playlists.addService(playlistActiveSensor);
groups.radio.addService(radioActiveSensor);
for (const [key, label, action] of [
  ["previous", "Playlist bài trước", "previous"], ["play", "Playlist phát tiếp", "resume"],
  ["pause", "Playlist tạm dừng", "pause"], ["next", "Playlist bài tiếp", "next"],
  ["stop", "Playlist dừng", "stop"],
]) addMomentaryMediaButton(groups.playlists, label, `playlist-${key}`, action);
for (const [key, label, action] of [
  ["play", "Radio phát tiếp", "resume"], ["pause", "Radio tạm dừng", "pause"], ["stop", "Radio dừng", "stop"],
]) addMomentaryMediaButton(groups.radio, label, `radio-${key}`, action);

const playlistRepeat = setControlName(new Service.Switch("Playlist lặp một bài", "playlist-repeat-one"), "Playlist lặp một bài");
const playlistShuffle = setControlName(new Service.Switch("Playlist phát ngẫu nhiên", "playlist-shuffle"), "Playlist phát ngẫu nhiên");
playlistRepeat.setCharacteristic(Characteristic.On, false).getCharacteristic(Characteristic.On)
  .onGet(() => Boolean(latest.playlistRepeatOne))
  .onSet(value => {
    if (Boolean(value) === Boolean(latest.playlistRepeatOne)) return Promise.resolve();
    return dispatchControl("Yêu cầu đổi lặp Playlist", () => client.runAction("repeat_toggle", false));
  });
playlistShuffle.setCharacteristic(Characteristic.On, false).getCharacteristic(Characteristic.On)
  .onGet(() => Boolean(latest.playlistShuffle))
  .onSet(value => {
    if (Boolean(value) === Boolean(latest.playlistShuffle)) return Promise.resolve();
    return dispatchControl("Yêu cầu đổi phát ngẫu nhiên", () => client.runAction("shuffle_toggle", false));
  });
groups.playlists.addService(playlistRepeat);
groups.playlists.addService(playlistShuffle);

function groupForAction(action) {
  if (action.startsWith("playlist:")) return groups.playlists;
  if (action.startsWith("radio:")) return groups.radio;
  if (/^(volume_|media_|repeat_|shuffle_|mute$|unmute$|play_all_local$)/.test(action)) return groups.media;
  if (/^(wakeup$|mic_|conversation_|wakeup_reply_|stop_tts$|cancel_wakeup$|speak_)/.test(action)) return groups.voice;
  return groups.system;
}

const actionControls = new Map();
const groupsWithPrimaryAction = new Set();
for (const definition of config.actionControls) {
  // Đánh thức đã có control API riêng; không tạo thêm tile trùng tên.
  if (["wakeup", "repeat_toggle", "shuffle_toggle"].includes(definition.id)) continue;
  const subtype = `vbot-action-${crypto.createHash("sha1").update(definition.id).digest("hex").slice(0, 12)}`;
  const control = setControlName(new Service.Switch(definition.label, subtype), definition.label);
  control.setCharacteristic(Characteristic.On, false);
  const group = groupForAction(definition.id);
  if (!groupsWithPrimaryAction.has(group) && ![groups.media, groups.voice].includes(group)) {
    control.setPrimaryService();
    groupsWithPrimaryAction.add(group);
  }
  group.addService(control);
  actionControls.set(definition.id, control);
}

// Không quảng bá một nhóm rỗng (ví dụ người dùng chưa cấu hình radio).
const bridgedGroups = [
  ...Object.values(groups).filter(child => child.services.length > 1),
  wakeupAccessory,
];
bridge.addBridgedAccessories(bridgedGroups);

bridge.on("advertised", () => console.log("[VBot_HomeKit_Node] Đã quảng bá VBot HomeKit Bridge qua mDNS"));
bridge.on("paired", () => console.log("[VBot_HomeKit_Node] Đã ghép đôi Bridge thành công với Apple Home"));
bridge.on("unpaired", () => console.log("[VBot_HomeKit_Node] Apple Home đã hủy ghép đôi Bridge"));
bridge.on("identify", (paired, callback) => {
  console.log(`[VBot_HomeKit_Node] Apple Home yêu cầu nhận diện Bridge (paired=${paired})`);
  callback();
});

const client = new VBotClient(config.vbot);
let latest = {
  volume: 0, muted: false, playback: "stopped", playbackRaw: "idle", sourceKind: "idle",
  micOn: true, conversationMode: false,
  wakeActive: false, wakeupReply: false, wakeInMedia: false, multipleCommand: false,
  continueListening: false, cacheTts: false, mediaPlayerActive: false, chatGpt: false,
  googleGemini: false, xiaozhi: false, defaultAssistant: false, olli: false,
  zaloAssistant: false, difyAi: false, devAssistant: false, processing: false,
};
let lastError = "";
let sseConnected = null;
let remoteStateLatch = null;
const REMOTE_STATE_LATCH_MS = 4000;

function mediaValue(state, target = false) {
  const type = target ? Characteristic.TargetMediaState : Characteristic.CurrentMediaState;
  if (state === "playing") return type.PLAY;
  if (state === "paused") return type.PAUSE;
  return type.STOP;
}

function logError(context, error) {
  const signature = `${context}:${error.message}`;
  if (signature !== lastError) console.error(`[VBot_HomeKit_Node] ${context}:`, error.message);
  lastError = signature;
}

async function runControl(context, operation) {
  try {
    await operation();
  } catch (error) {
    logError(context, error);
    throw error;
  }
}

// RemoteKey là event tức thời. Không giữ transaction HAP chờ HTTP VBot vì
// iOS có thể xếp hàng các phím sau và tạo cảm giác Remote bị treo.
function dispatchRemoteControl(context, operation) {
  void runControl(context, operation).catch(() => {});
  return Promise.resolve();
}

// HAP chỉ xác nhận đã nhận yêu cầu; kết quả thật được cập nhật lại bằng SSE.
// Không trả Promise HTTP cho onSet vì một API/TTS chậm sẽ khóa hàng đợi ghi của iOS.
function dispatchControl(context, operation) {
  void runControl(context, operation).catch(() => {});
  return Promise.resolve();
}

function dispatchConfiguredAction(context, action, remoteKey = null) {
  const source = remoteKey ? "homekit_remote" : null;
  if (action === "volume_up" || action === "volume_down") {
    const direction = action === "volume_up" ? "up" : "down";
    void runControl(context, () => client.changeVolume(direction, source, remoteKey)).catch(() => {});
    return Promise.resolve();
  }
  return dispatchRemoteControl(context, () => client.runAction(action, false, source, remoteKey));
}

function currentRemoteState() {
  if (latest.recording) return "recording";
  if (latest.ttsActive || ["tts", "system_sound"].includes(latest.sourceKind)) return "tts";
  if (latest.playback === "paused") return "media_paused";
  if (latest.playlistActive) return "playlist";
  if (latest.sourceKind !== "idle" || latest.playbackRaw !== "idle") return "media";
  return "idle";
}

function remoteButtonAction(key) {
  // Tren mot so phien ban iOS, cung thao tac bam vung trai/phai co the duoc
  // gui thanh PREVIOUS/NEXT_TRACK thay vi ARROW_LEFT/RIGHT. Dung chung mot
  // cau hinh de tranh iOS chay action mac dinh khac voi "Phim Trai/Phai".
  const canonicalKey = {
    previous_track: "arrow_left",
    rewind: "arrow_left",
    next_track: "arrow_right",
    fast_forward: "arrow_right",
  }[key] || key;
  const definition = config.remoteButtons[canonicalKey];
  if (!definition) return "none";
  const now = Date.now();
  // Feedback am thanh cua chinh action co the lam SSE tam thoi chuyen sang TTS.
  // Trong mot chuoi bam lien tiep, giu trang thai tai lan bam dau de cung mot
  // phim khong doi action. Sau 4 giay khong bam moi danh gia trang thai lai.
  const state = remoteStateLatch && remoteStateLatch.until > now
    ? remoteStateLatch.state
    : currentRemoteState();
  remoteStateLatch = { state, until: now + REMOTE_STATE_LATCH_MS };
  const action = definition.actionsByState?.[state] || definition.action || "none";
  return action;
}

if (speaker) {
  speaker.getCharacteristic(Characteristic.Volume).onGet(() => sliderDisplayValue("vbot-local-volume", latest.volume))
    .onSet(value => debounceSliderControl("vbot-local-volume", value, finalValue =>
      runControl(`Yêu cầu đổi âm lượng ${finalValue}%`, () => client.setVolume(finalValue))));
  speaker.getCharacteristic(Characteristic.Mute).onGet(() => latest.muted)
    .onSet(value => dispatchControl("Yêu cầu đổi trạng thái tắt tiếng", () => client.setMute(Boolean(value))));
  speaker.getCharacteristic(Characteristic.CurrentMediaState).onGet(() => mediaValue(latest.playback));
  speaker.getCharacteristic(Characteristic.TargetMediaState).onGet(() => mediaValue(latest.playback, true)).onSet(value => {
    const actions = {
      [Characteristic.TargetMediaState.PLAY]: "resume",
      [Characteristic.TargetMediaState.PAUSE]: "pause",
      [Characteristic.TargetMediaState.STOP]: "stop",
    };
    return dispatchControl("Yêu cầu đổi trạng thái media", () => client.mediaAction(actions[value], false));
  });
}
if (television) {
  television.getCharacteristic(Characteristic.Active)
    // Active của Television biểu thị Remote/API đang sẵn sàng, không phải
    // playback. iPhone gửi Active=ON khi mở Remote nên phải bỏ qua ON; nút
    // nguồn trên Remote gửi Active=OFF và được ánh xạ tới cấu hình power.
    .onGet(() => Characteristic.Active.ACTIVE)
    .onSet(value => {
      if (value !== Characteristic.Active.INACTIVE) return Promise.resolve();
      const action = remoteButtonAction("power");
      return action !== "none"
        ? dispatchConfiguredAction(`Remote power: ${action}`, action, "power")
        : Promise.resolve();
    });
  television.getCharacteristic(Characteristic.RemoteKey).onSet(value => {
    const keys = {
      [Characteristic.RemoteKey.REWIND]: "rewind",
      [Characteristic.RemoteKey.FAST_FORWARD]: "fast_forward",
      [Characteristic.RemoteKey.NEXT_TRACK]: "next_track",
      [Characteristic.RemoteKey.PREVIOUS_TRACK]: "previous_track",
      [Characteristic.RemoteKey.ARROW_UP]: "arrow_up",
      [Characteristic.RemoteKey.ARROW_DOWN]: "arrow_down",
      [Characteristic.RemoteKey.ARROW_LEFT]: "arrow_left",
      [Characteristic.RemoteKey.ARROW_RIGHT]: "arrow_right",
      [Characteristic.RemoteKey.SELECT]: "select",
      [Characteristic.RemoteKey.BACK]: "back",
      [Characteristic.RemoteKey.EXIT]: "exit",
      [Characteristic.RemoteKey.PLAY_PAUSE]: "play_pause",
      [Characteristic.RemoteKey.INFORMATION]: "information",
    };
    const key = keys[value];
    const action = key && remoteButtonAction(key);
    if (!action || action === "none") return Promise.resolve();
    return dispatchConfiguredAction(`Remote ${key}: ${action}`, action, key);
  });
  // PowerModeSelection là menu/chế độ xem của Television, không phải nút
  // nguồn trên Apple Remote. Không gán action tại đây để tránh phát lệnh đôi.
  television.getCharacteristic(Characteristic.PowerModeSelection)
    .onSet(() => Promise.resolve());
  televisionSpeaker.getCharacteristic(Characteristic.Mute)
    .onGet(() => latest.muted)
    .onSet(value => {
      const muted = Boolean(value);
      const action = remoteButtonAction("mute");
      if (!action || action === "none") return Promise.resolve();
      if (action !== "mute") {
        return dispatchConfiguredAction(`Remote mute: ${action}`, action, "mute");
      }
      // Phan hoi ngay tren Remote; SSE se dong bo lai bang trang thai API that.
      latest = { ...latest, muted };
      if (speaker) speaker.updateCharacteristic(Characteristic.Mute, muted);
      muteControl.updateCharacteristic(Characteristic.On, muted);
      return dispatchControl(
        muted ? "Yêu cầu tắt tiếng từ VBot Remote" : "Yêu cầu mở tiếng từ VBot Remote",
        () => client.setMute(muted, "homekit_remote", "mute"),
      );
    });
  televisionSpeaker.getCharacteristic(Characteristic.VolumeSelector).onSet(value => {
    const key = value === Characteristic.VolumeSelector.INCREMENT ? "volume_up" : "volume_down";
    const action = remoteButtonAction(key);
    if (!action || action === "none") return Promise.resolve();
    return dispatchConfiguredAction(`Remote ${key}: ${action}`, action, key);
  });
}

playbackControl.getCharacteristic(Characteristic.On).onGet(() => latest.playback === "playing").onSet(value =>
  dispatchControl(value ? "Yêu cầu phát hoặc tiếp tục" : "Yêu cầu tạm dừng", () => client.mediaAction(value ? "resume" : "pause", false)));
muteControl.getCharacteristic(Characteristic.On).onGet(() => latest.muted).onSet(value =>
  dispatchControl(value ? "Yêu cầu tắt tiếng" : "Yêu cầu mở tiếng", () => client.setMute(Boolean(value))));
if (volumeControl) {
  volumeControl.getCharacteristic(Characteristic.On).onGet(() => latest.volume > 0).onSet(value => {
    if (value || latest.volume === 0) return Promise.resolve();
    return dispatchControl("Yêu cầu âm lượng 0%", () => client.setVolume(0));
  });
  volumeControl.getCharacteristic(Characteristic.Brightness)
    .onGet(() => sliderDisplayValue("vbot-local-volume", latest.volume))
    .onSet(value => debounceSliderControl("vbot-local-volume", value, finalValue =>
      runControl(`Yêu cầu âm lượng ${finalValue}%`, () => client.setVolume(finalValue))));
}
wakeupControl.getCharacteristic(Characteristic.On).onGet(() => false).onSet(value => {
  if (!value) return Promise.resolve();
  resetMomentaryControl(wakeupControl);
  return dispatchControl("Yêu cầu đánh thức VBot", () => client.wakeUp());
});
micControl.getCharacteristic(Characteristic.On).onGet(() => latest.micOn).onSet(value =>
  dispatchControl(value ? "Yêu cầu bật Micro" : "Yêu cầu tắt Micro", () => client.setFeature("mic_on_off", value)));
conversationControl.getCharacteristic(Characteristic.On).onGet(() => latest.conversationMode).onSet(value =>
  dispatchControl(value ? "Yêu cầu bật chế độ hội thoại" : "Yêu cầu tắt chế độ hội thoại", () => client.setFeature("conversation_mode", value)));

for (const [action, control] of actionControls) {
  control.getCharacteristic(Characteristic.On).onGet(() => false).onSet(value => {
    if (!value) return Promise.resolve();
    resetMomentaryControl(control);
    return dispatchConfiguredAction(`Yêu cầu action ${action}`, action);
  });
}

client.on("state", state => {
  latest = state;
  lastError = "";
  if (speaker) {
    speaker.updateCharacteristic(Characteristic.Volume, sliderDisplayValue("vbot-local-volume", state.volume));
    speaker.updateCharacteristic(Characteristic.Mute, state.muted);
    speaker.updateCharacteristic(Characteristic.CurrentMediaState, mediaValue(state.playback));
    speaker.updateCharacteristic(Characteristic.TargetMediaState, mediaValue(state.playback, true));
  }
  if (television) television.updateCharacteristic(Characteristic.Active, Characteristic.Active.ACTIVE);
  if (televisionSpeaker) televisionSpeaker.updateCharacteristic(Characteristic.Mute, state.muted);
  playbackControl.updateCharacteristic(Characteristic.On, state.playback === "playing");
  muteControl.updateCharacteristic(Characteristic.On, state.muted);
  if (volumeControl) {
    const displayedVolume = sliderDisplayValue("vbot-local-volume", state.volume);
    volumeControl.updateCharacteristic(Characteristic.On, displayedVolume > 0);
    volumeControl.updateCharacteristic(Characteristic.Brightness, displayedVolume);
  }
  wakeupControl.updateCharacteristic(Characteristic.On, false);
  micControl.updateCharacteristic(Characteristic.On, state.micOn);
  conversationControl.updateCharacteristic(Characteristic.On, state.conversationMode);
  playlistActiveSensor.updateCharacteristic(
    Characteristic.OccupancyDetected,
    state.playlistActive ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
  );
  radioActiveSensor.updateCharacteristic(
    Characteristic.OccupancyDetected,
    state.radioSource && state.playback === "playing" ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
  );
  playlistRepeat.updateCharacteristic(Characteristic.On, state.playlistRepeatOne);
  playlistShuffle.updateCharacteristic(Characteristic.On, state.playlistShuffle);
  for (const item of stateSwitches) item.control.updateCharacteristic(Characteristic.On, Boolean(state[item.stateKey]));
  for (const item of registryStateControls) {
    const value = state[item.definition.stateKey];
    if (item.definition.service === "brightness" || item.definition.service === "volume") {
      const sliderKey = item.definition.apiMethod === "local_volume" ? "vbot-local-volume" : item.definition.id;
      const displayedValue = sliderDisplayValue(sliderKey, value);
      item.control.updateCharacteristic(Characteristic.On, displayedValue > 0);
      item.control.updateCharacteristic(Characteristic.Brightness, displayedValue);
    } else if (item.definition.service === "percentage") {
      item.control.updateCharacteristic(
        Characteristic.CurrentRelativeHumidity,
        Math.max(0, Math.min(100, Number(value || 0))),
      );
    } else if (item.definition.service === "occupancy") {
      item.control.updateCharacteristic(
        Characteristic.OccupancyDetected,
        value ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
      );
    } else item.control.updateCharacteristic(Characteristic.On, Boolean(value));
  }
  processingSensor.updateCharacteristic(
    Characteristic.OccupancyDetected,
    state.processing ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
  );
});
const discoveredMultiroomSpeakers = new Map();
client.on("multiroomState", snapshot => {
  const controller = snapshot.controller || {};
  const active = new Map((controller.speakers || []).map(item => [String(item.id || "").toLowerCase(), item]));
  const discoveredAt = Date.now();
  for (const item of snapshot.devices || []) {
    const id = String(item.id || "").toLowerCase();
    if (id) discoveredMultiroomSpeakers.set(id, discoveredAt);
  }
  for (const [id, controls] of multiroomSpeakerControls) {
    const speakerState = active.get(id);
    const recentlyDiscovered = discoveredAt - Number(discoveredMultiroomSpeakers.get(id) || 0) < 45000;
    controls.state = {
      joined: Boolean(speakerState),
      muted: Boolean(speakerState?.muted),
      volume: Number(speakerState?.volume || 0),
      online: Boolean(speakerState?.online || recentlyDiscovered),
    };
    controls.joined.updateCharacteristic(Characteristic.On, controls.state.joined);
    controls.muted.updateCharacteristic(Characteristic.On, controls.state.muted);
    const displayedSpeakerVolume = sliderDisplayValue(`multiroom-speaker-${id}`, controls.state.volume);
    controls.volume.updateCharacteristic(Characteristic.On, displayedSpeakerVolume > 0);
    controls.volume.updateCharacteristic(Characteristic.Brightness, displayedSpeakerVolume);
    controls.online.updateCharacteristic(
      Characteristic.OccupancyDetected,
      controls.state.online ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
    );
  }
});
client.on("online", online => onlineSensor.updateCharacteristic(
  Characteristic.OccupancyDetected,
  online ? Characteristic.OccupancyDetected.OCCUPANCY_DETECTED : Characteristic.OccupancyDetected.OCCUPANCY_NOT_DETECTED,
));
client.on("clientError", error => logError("VBot API chưa sẵn sàng", error));
client.on("sse", connected => {
  if (connected === sseConnected) return;
  sseConnected = connected;
  if (connected) {
    lastError = "";
    console.log("[VBot_HomeKit_Node] Đã kết nối luồng trạng thái SSE của chương trình VBot");
  } else console.warn("[VBot_HomeKit_Node] Mất kết nối SSE, sẽ tự kết nối lại");
});

bridge.publish({
  username: config.username,
  pincode: config.pincode,
  port: config.port,
  category: Categories.BRIDGE,
  setupID: config.setupID,
});

// QR chứa HomeKit Setup URI chuẩn do chính HAP-NodeJS sinh. Lưu ngoài Apache
// DocumentRoot; Config.php chỉ nhúng nội dung sau khi người dùng đã đăng nhập.
const pairingQrPath = path.join(path.dirname(config.persistPath), "HomeKit_Pairing_QR.svg");
async function createPairingQr() {
  try {
    const pairingDirectory = path.dirname(pairingQrPath);
    console.log(`[VBot_HomeKit_Node] Bắt đầu tạo mã QR tại: ${pairingQrPath}`);
    fs.mkdirSync(pairingDirectory, { recursive: true, mode: 0o777 });
    fs.chmodSync(pairingDirectory, 0o777);
    const setupUri = bridge.setupURI();
    console.log(`[VBot_HomeKit_Node] Đã tạo HomeKit Setup URI, đang kết xuất QR code SVG`);
    let timeoutId;
    const timeout = new Promise((_, reject) => {
      timeoutId = setTimeout(() => reject(new Error("Quá thời gian 10 giây khi kết xuất QR SVG")), 10000);
    });
    const svg = await Promise.race([
      QRCode.toString(setupUri, { type: "svg", errorCorrectionLevel: "M", margin: 2, width: 360 }),
      timeout,
    ]).finally(() => clearTimeout(timeoutId));
    fs.writeFileSync(pairingQrPath, svg, { encoding: "utf8", mode: 0o777 });
    fs.chmodSync(pairingQrPath, 0o777);
    console.log(`[VBot_HomeKit_Node] Đã tạo mã QR ghép đôi: ${pairingQrPath}`);
  } catch (error) {
    logError(`Không thể tạo mã QR ghép đôi tại ${pairingQrPath}`, error);
  }
}
setTimeout(() => void createPairingQr(), 250);
client.start();
console.log(`[VBot_HomeKit_Node] ${bridgeName} đang chạy tại cổng ${config.port}, ID ${config.username}`);
console.log(`[VBot_HomeKit_Node] Kiểu phụ kiện chính: ${config.accessoryType}`);
console.log(`[VBot_HomeKit_Node] VBot API: ${config.vbot.baseUrl} (đọc từ ${config.configPath})`);
console.log(`[VBot_HomeKit_Node] Bridge có ${bridgedGroups.length} accessory và ${actionControls.size} nút action`);
console.log(`[VBot_HomeKit_Node] Đã nạp ${registryStateControls.length} control từ homekit_registry.json`);

function shutdown(signal) {
  console.log(`[VBot_HomeKit_Node] Nhận ${signal}, đang dừng...`);
  client.stop();
  for (const timer of momentaryResetTimers.values()) clearTimeout(timer);
  momentaryResetTimers.clear();
  for (const entry of sliderWriteTimers.values()) clearTimeout(entry.timer);
  sliderWriteTimers.clear();
  remoteStateLatch = null;
  bridge.unpublish();
  setTimeout(() => process.exit(0), 100).unref();
}
process.once("SIGTERM", () => shutdown("SIGTERM"));
process.once("SIGINT", () => shutdown("SIGINT"));
