"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");
const { VBotClient, normalizeState, parseSseFrame } = require("../lib/vbot-client");
const { autoUsername, buildConfig, loadRemoteButtons, raspberryPiOtpSerial, sanitizeHomeKitName } = require("../lib/config");

test("normalizeState maps VBot media state", () => {
  assert.deepEqual(normalizeState({
    volume: 64,
    playback_state: "playing",
    audio_output: { muted: true },
    media_name: "Bài hát",
    media_player_source: "Local",
    mic_on_off: false,
    conversation_mode: true,
  }), {
    volume: 64, muted: true, playback: "playing", playbackRaw: "playing",
    title: "Bài hát", source: "Local", sourceKind: "idle",
    micOn: false, conversationMode: true, wakeActive: false,
    wakeupReply: false, wakeInMedia: false, multipleCommand: false,
    continueListening: false, cacheTts: false, mediaPlayerActive: false,
    chatGpt: false, googleGemini: false, xiaozhi: false, defaultAssistant: false,
    olli: false, zaloAssistant: false, difyAi: false, devAssistant: false, processing: false,
    ledBrightness: 0, musicLocal: false, podcast: false, radioSource: false,
    youtube: false, zingMp3: false, nhacCuaTui: false, airplayActive: false,
    playlistActive: false, playlistId: "", playlistName: "", playlistMode: "",
    playlistLoop: false, playlistRepeatOne: false, playlistShuffle: false,
    bluetoothActive: false, multiroomActive: false,
    waitingWakeup: false, recording: false, sttProcessing: false,
    assistantProcessing: false, ttsActive: false, mediaPlaying: true,
    mqttEnabled: false, mqttConnected: false, multiroomCoordinator: false,
    multiroomClient: false, multiroomPaused: false, multiroomMuted: false,
    multiroomVolume: 0, multiroomMasterVolume: 64,
  });
});

test("client sends existing VBot API contracts and auth header", async () => {
  const calls = [];
  const fakeFetch = async (url, options) => {
    calls.push({ url, options });
    return { ok: true, json: async () => ({ success: true }) };
  };
  const client = new VBotClient({
    baseUrl: "http://127.0.0.1:5002", apiKey: "secret", requestTimeoutMs: 1000,
    ssePath: "/?type=1&data=media_player&stream=sse", sseReconnectMs: 1000,
  }, fakeFetch);
  await client.setVolume(150);
  await client.setMute(true);
  await client.mediaAction("pause");
  await client.setFeature("mic_on_off", false);
  await client.wakeUp();
  await client.runAction("media_next");
  await client.setMultiroomMute(true);
  assert.equal(calls[0].options.headers["VBot-API-Key"], "secret");
  assert.deepEqual(JSON.parse(calls[0].options.body), { type: 2, data: "volume", action: "setup", value: 100 });
  assert.deepEqual(JSON.parse(calls[1].options.body), { type: 2, data: "audio_output", action: "mute" });
  assert.deepEqual(JSON.parse(calls[2].options.body), { type: 1, data: "media_control", action: "pause" });
  assert.deepEqual(JSON.parse(calls[3].options.body), { type: 2, data: "mic_on_off", action: false });
  assert.deepEqual(JSON.parse(calls[4].options.body), { type: 2, data: "wake_up", action: true });
  assert.deepEqual(JSON.parse(calls[5].options.body), { type: 1, data: "media_control", action: "next" });
  assert.equal(calls[6].url, "http://127.0.0.1:5002/multiroom");
  assert.deepEqual(JSON.parse(calls[6].options.body), { action: "set_mute", muted: true });
});

test("normalizeState reads nested Bluetooth state from all_info SSE", () => {
  assert.equal(normalizeState({ bluetooth: { active: true } }).bluetoothActive, true);
});

test("normalizeState preserves canonical fields used by state-aware Remote keys", () => {
  const state = normalizeState({
    media_player: { source_kind: "system_sound", playback_state: "paused" },
    tts_active: true,
  });
  assert.equal(state.sourceKind, "system_sound");
  assert.equal(state.playbackRaw, "paused");
  assert.equal(state.ttsActive, true);
});

test("Remote button loader supports legacy strings and six state mappings", () => {
  const controls = [
    { id: "volume_up" }, { id: "volume_down" }, { id: "wakeup" },
  ];
  const buttons = loadRemoteButtons({
    remote_buttons: {
      arrow_up: "wakeup",
      arrow_down: {
        action: "volume_down",
        actions_by_state: { idle: "wakeup", recording: "none" },
      },
    },
  }, controls);
  assert.equal(buttons.arrow_up.action, "wakeup");
  assert.equal(buttons.arrow_up.actionsByState.playlist, "wakeup");
  assert.equal(buttons.arrow_down.actionsByState.idle, "wakeup");
  assert.equal(buttons.arrow_down.actionsByState.recording, "none");
  assert.deepEqual(Object.keys(buttons.arrow_down.actionsByState), [
    "playlist", "idle", "media", "media_paused", "tts", "recording",
  ]);
});

test("duplicate HomeKit media commands are coalesced", async () => {
  let calls = 0;
  const client = new VBotClient({
    baseUrl: "http://127.0.0.1:5002", apiKey: "", requestTimeoutMs: 1000,
    ssePath: "/events", sseReconnectMs: 1000,
  }, async () => {
    calls += 1;
    return { ok: true, json: async () => ({ success: true }) };
  });
  await Promise.all([client.mediaAction("pause"), client.mediaAction("pause"), client.mediaAction("pause")]);
  assert.equal(calls, 1);
});

test("remote actions can bypass coalescing so rapid key presses are not lost", async () => {
  let calls = 0;
  const client = new VBotClient({
    baseUrl: "http://127.0.0.1:5002", apiKey: "", requestTimeoutMs: 1000,
    ssePath: "/events", sseReconnectMs: 1000,
  }, async () => {
    calls += 1;
    return { ok: true, json: async () => ({ success: true }) };
  });
  await Promise.all([
    client.runAction("volume_up", false),
    client.runAction("volume_up", false),
    client.runAction("volume_up", false),
  ]);
  assert.equal(calls, 3);
});

test("latency-sensitive actions use direct APIs instead of virtual button registry", async () => {
  const calls = [];
  const client = new VBotClient({
    baseUrl: "http://127.0.0.1:5002", apiKey: "", requestTimeoutMs: 1000,
    ssePath: "/events", sseReconnectMs: 1000,
  }, async (_url, options) => {
    calls.push(JSON.parse(options.body));
    return { ok: true, json: async () => ({ success: true }) };
  });
  await client.runAction("volume_up", false);
  await client.runAction("volume_down", false);
  await client.runAction("mute", false);
  await client.runAction("media_next", false);
  assert.deepEqual(calls, [
    { type: 2, data: "volume", action: "up" },
    { type: 2, data: "volume", action: "down" },
    { type: 2, data: "audio_output", action: "mute" },
    { type: 1, data: "media_control", action: "next" },
  ]);
});

test("SSE parser ignores heartbeat and reads update payload", () => {
  assert.equal(parseSseFrame(": vbot-keepalive"), null);
  assert.deepEqual(
    parseSseFrame('event: update\ndata: {"success":true,"volume":42,"playback_state":"paused"}'),
    { success: true, volume: 42, playback_state: "paused" },
  );
});

test("client logs only the third consecutive timeout and resets after success", () => {
  const client = new VBotClient({
    baseUrl: "http://127.0.0.1:5002", apiKey: "", requestTimeoutMs: 4000,
    ssePath: "/events", sseIntervalSeconds: 2, sseReconnectMs: 1000,
  }, async () => ({ ok: true, json: async () => ({ success: true }) }));
  const errors = [];
  client.on("clientError", error => errors.push(error.message));
  const timeout = new Error("VBot API timeout sau 4000ms (GET /multiroom?discover=true)");

  client.reportClientError("multiroom-discovery", timeout);
  client.reportClientError("multiroom-discovery", timeout);
  assert.equal(errors.length, 0);
  client.reportClientError("multiroom-discovery", timeout);
  assert.equal(errors.length, 1);
  client.reportClientError("multiroom-discovery", timeout);
  assert.equal(errors.length, 1);

  client.markClientSuccess("multiroom-discovery");
  client.reportClientError("multiroom-discovery", timeout);
  client.reportClientError("multiroom-discovery", timeout);
  client.reportClientError("multiroom-discovery", timeout);
  assert.equal(errors.length, 2);
});

test("automatic HomeKit username is stable and valid", () => {
  assert.equal(autoUsername("VBot Assistant", "ABC123"), autoUsername("VBot Assistant", "ABC123"));
  assert.notEqual(autoUsername("VBot Assistant", "ABC123"), autoUsername("VBot Assistant", "DEF456"));
  assert.match(autoUsername("VBot Assistant", "ABC123"), /^02(:[0-9A-F]{2}){5}$/);
});

test("Raspberry Pi OTP row 28 is parsed without shell grep", () => {
  const calls = [];
  const serial = raspberryPiOtpSerial((command, args, options) => {
    calls.push({ command, args, options });
    return "27:00000000\n28:1a2b3c4d\n29:00000000\n";
  });
  assert.equal(serial, "1A2B3C4D");
  assert.equal(calls[0].command, "vcgencmd");
  assert.deepEqual(calls[0].args, ["otp_dump"]);
});

test("HomeKit name keeps Vietnamese text and removes unsupported emoji", () => {
  assert.equal(sanitizeHomeKitName("  Vũ Tuyển DEV  😃  "), "Vũ Tuyển DEV");
});

test("HomeKit and API settings come from the main VBot Config.json", () => {
  const config = buildConfig({
    api: { port: 9876, auth: { active: true, api_key: "secret" } },
    media_player: { media_sync_ui: { delay_time: 3 } },
    contact_info: { full_name: "VBot Phòng Khách" },
    homekit: {
      active: true, accessory_type: "television", manufacturer: "VBot", model: "Speaker",
      serial_number: "VBot-1", pincode: "031-45-154",
      username: "auto", port: 51826,
      request_timeout_ms: 4000, sse_reconnect_ms: 3000,
    },
  }, "/vbot/Config.json", { version: "1.2.3" });
  assert.equal(config.vbot.baseUrl, "http://127.0.0.1:9876");
  assert.equal(config.accessoryType, "television");
  assert.equal(config.vbot.apiKey, "secret");
  assert.equal(config.name, "VBot Phòng Khách");
  assert.equal(config.firmwareRevision, "1.2.3");
  assert.equal(config.persistPath, "/home/pi/VBot_Node/HomeKit/persist");
  assert.equal(config.vbot.sseIntervalSeconds, 3);
  assert.equal(config.vbot.ssePath, "/?type=1&data=all_info&stream=sse&interval=3");
});
