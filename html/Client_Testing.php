<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
  session_start();
  if (
    !isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
  ) {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
  <style>
    .log-info { color: #a8ff9f; }
    .log-ok { color: #7ee0a1; }
    .log-warn { color: #ffd166; }
    .log-error { color: #ff7f7f; }
    .log-server { color: #8bd3ff; }
  </style>
</head>
<body>
  <!-- ======= Header ======= -->
  <?php
  include 'html_header_bar.php';
  ?>
  
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php
  include 'html_sidebar.php';
  ?>
  <!-- End Sidebar-->

  <main id="main" class="main">



    <div class="pagetitle">
      <h1>Trình Kiểm Thử Kết Nối Client - Server Giao Thức Socket</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item">Người dùng</li>
          <li class="breadcrumb-item active">Demo Client Tester Chế Độ Socket</li>
			&nbsp;| Trạng Thái Kích Hoạt:
			<span class="<?php echo $Config['api']['streaming_server']['connection_protocol'] === 'socket' ? 'text-success' : 'text-danger'; ?>">
				&nbsp;<?php echo $Config['api']['streaming_server']['connection_protocol'] === 'socket' ? 'Đang Bật' : 'Đang Tắt'; ?>
			</span>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <div class="row g-3">
      <div class="col-lg-4 col-md-5">
        <section class="card p-3 h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">Cấu Hình Kết Nối</h5>
            <button id="helpBtn" type="button" class="btn btn-outline-info btn-sm">
              <i class="bi bi-question-circle me-1"></i>Hướng dẫn
            </button>
          </div>
          <div id="helpText" class="alert alert-info py-2 px-3 small d-none">
            <div><strong>Nếu Trình duyệt không hỗ trợ microphone trên HTTP local</strong></div>
            <div>URL hiện tại: <span id="currentUrl"></span></div>
            <div>Cách khắc phục:</div>
            <div>- Dùng Trình Duyệt Chrome sửa trong Flag (test tạm)</div>
            <div>- Mở: chrome://flags/#unsafely-treat-insecure-origin-as-secure</div>
            <div>- Bật: Insecure origins treated as secure</div>
            <div>- Thêm URL local hiện tại vào ô: http://ip/</div>
            <div>- Sau đó khởi động lại Chrome</div>
          </div>

          <label for="serverUrl" class="text-success">WebSocket URL:</label>
          <input id="serverUrl" class="form-control border-success" value="ws://192.168.14.175:5003" autocomplete="off">
			<br/>
          <label for="sessionId" class="text-success">Session ID:</label>
          <input id="sessionId" class="form-control border-success" autocomplete="off">
			<br/>
          <label for="workingMode" class="text-success">Chế độ làm việc:</label>
          <select id="workingMode" class="form-select border-success">
            <option value="main_processing" selected>Luồng xử lý chính VBot</option>
            <option value="chatbot">Luồng xử lý Chatbot</option>
            <option value="stt_to_tts">Chỉ chuyển đổi âm thanh sang văn bản text</option>
          </select>
			<br/>
          <div class="form-check form-switch mt-2">
            <input id="playPcmAudio" class="form-check-input" type="checkbox" checked>
            <label class="form-check-label" for="playPcmAudio">Tự động phát Audio PCM RAW</label>
          </div>
          <div class="form-check form-switch">
            <input id="playTtsAudio" class="form-check-input" type="checkbox" checked>
            <label class="form-check-label" for="playTtsAudio">Tự động phát TTS Audio</label>
          </div>
          <div class="form-check form-switch">
            <input id="playStatusAudio" class="form-check-input" type="checkbox" checked>
            <label class="form-check-label" for="playStatusAudio">Tự động phát Status Audio</label>
          </div>
          <div class="form-check form-switch">
            <input id="conversationMode" class="form-check-input" type="checkbox" checked>
            <label class="form-check-label" for="conversationMode">Chế độ hội thoại</label>
          </div>

          <div class="row mt-2">
            <div class="col-6">
              <label for="sampleRate" class="text-danger">Sample rate</label>
              <select id="sampleRate" class="form-select text-danger border-danger">
                <option value="16000" selected>16000 Hz</option>
                <option value="48000">48000 Hz</option>
              </select>
            </div>
            <div class="col-6">
              <label for="chunkSize" class="text-danger">Frame samples</label>
              <input id="chunkSize" class="form-control text-danger border-danger" value="512" inputmode="numeric">
            </div>
          </div>

          <div class="d-grid gap-2 mt-3">
            <div class="btn-group" role="group">
              <button id="connectBtn" class="btn btn-primary"><i class="bi bi-link-45deg"></i> Kết nối máy chủ </button>
              <button id="disconnectBtn" class="btn btn-secondary" disabled><i class="bi bi-dash-circle me-1"></i> Ngắt kết Nối</button>
            </div>
            <div class="btn-group" role="group">
              <button id="startBtn" class="btn btn-success" disabled><i class="bi bi-mic-fill me-1"></i> Bật Mic</button>
              <button id="stopBtn" class="btn btn-danger" disabled><i class="bi bi-mic-mute-fill me-1"></i> Tắt Mic</button>
            </div>
            <div class="btn-group" role="group">
              <button id="wakeUpBtn" class="btn btn-primary"><i class="bi bi-caret-right"></i> Wake UP</button>
              <button id="wakeBtn" class="btn btn-success" disabled><i class="bi bi-record-circle me-1"></i> Bắt Đầu Thu Âm</button>
            </div>
            <div class="btn-group" role="group">
              <button id="stopUrlAudioBtn" class="btn btn-warning"><i class="bi bi-stop-circle me-1"></i> Dừng Phát Âm Thanh</button>
              <button id="clearBtn" class="btn btn-danger"><i class="bi bi-trash-fill me-1"></i> Xóa Log</button>
            </div>
          </div>

          <div class="mt-3">
            <div class="row text-center">
              <div class="col-4 border-end">
                <div class="small text-muted">Socket</div>
                <div><strong id="socketState">Chưa kết nối</strong></div>
              </div>
              <div class="col-4 border-end">
                <div class="small text-muted">Mic</div>
                <div><strong id="micState">Tắt</strong></div>
              </div>
              <div class="col-4">
                <div class="small text-muted">Frames</div>
                <div><strong id="frameCount">0</strong></div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="col-lg-8 col-md-7">
        <section class="card p-3 h-100">
          <h4>Log dữ liệu</h4>
          <div id="log" aria-live="polite" class="bg-dark text-light p-3 rounded" style="height:calc(80vh); overflow:auto; font-family: ui-monospace, SFMono-Regular, Consolas, 'Liberation Mono', monospace; white-space:pre-wrap; font-size:13px;"></div>
        </section>
      </div>
    </div>




  </main>


  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <?php
  include 'html_js.php';
  ?>


  <script>
    const els = {
      serverUrl: document.getElementById("serverUrl"),
      sessionId: document.getElementById("sessionId"),
      workingMode: document.getElementById("workingMode"),
      playPcmAudio: document.getElementById("playPcmAudio"),
      playTtsAudio: document.getElementById("playTtsAudio"),
      playStatusAudio: document.getElementById("playStatusAudio"),
      conversationMode: document.getElementById("conversationMode"),
      helpBtn: document.getElementById("helpBtn"),
      helpText: document.getElementById("helpText"),
      currentUrl: document.getElementById("currentUrl"),
      sampleRate: document.getElementById("sampleRate"),
      chunkSize: document.getElementById("chunkSize"),
      connectBtn: document.getElementById("connectBtn"),
      disconnectBtn: document.getElementById("disconnectBtn"),
      startBtn: document.getElementById("startBtn"),
      stopBtn: document.getElementById("stopBtn"),
      wakeBtn: document.getElementById("wakeBtn"),
	  wakeUpBtn: document.getElementById("wakeUpBtn"),
      stopUrlAudioBtn: document.getElementById("stopUrlAudioBtn"),
      clearBtn: document.getElementById("clearBtn"),
      socketState: document.getElementById("socketState"),
      micState: document.getElementById("micState"),
      frameCount: document.getElementById("frameCount"),
      log: document.getElementById("log")
    };

    let socket = null;
    let audioContext = null;
    let mediaStream = null;
    let sourceNode = null;
    let processorNode = null;
    let isRecording = false;
    let frameCount = 0;
    let pendingBinaryAudioMeta = null;
    let pcmReceiving = false;
    let pcmReceivingResetTimer = null;
    const pcmSources = new Set();
    let pendingContinueWakeUp = false;
    let continueWakeUpTimer = null;
    let playbackContext = null;
    let nextPcmPlaybackTime = 0;
    const urlAudioPlayers = new Set();

    function makeSessionId() {
      return "VBot_Demo_Client_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2, 8);
    }

    function log(message, type = "info") {
      const time = new Date().toLocaleTimeString();
      const line = document.createElement("div");
      line.className = "log-" + type;
      line.textContent = `[${time}] ${message}`;
      els.log.appendChild(line);
      els.log.scrollTop = els.log.scrollHeight;
    }

    function setSocketState(text) {
      els.socketState.textContent = text;
    }

    function setMicState(text) {
      els.micState.textContent = text;
    }

    function setConnectedState(connected) {
      els.connectBtn.disabled = connected;
      els.disconnectBtn.disabled = !connected;
      els.startBtn.disabled = !connected || isRecording;
      els.stopBtn.disabled = !connected || !isRecording;
      els.wakeBtn.disabled = !connected;
      setSocketState(connected ? "Đã kết nối" : "Chưa kết nối");
    }

    function ensureSessionId() {
      if (!els.sessionId.value.trim()) {
        els.sessionId.value = makeSessionId();
      }
      return els.sessionId.value.trim();
    }

    function sendText(text) {
      if (!socket || socket.readyState !== WebSocket.OPEN) {
        log("Socket chưa sẵn sàng", "warn");
        return false;
      }
      socket.send(text);
      log("Client -> " + text, "info");
      return true;
    }

    function sendClientConfig() {
      const config = {
        session_id: ensureSessionId(),
        working_mode: els.workingMode.value
      };
      return sendText(JSON.stringify(config));
    }

    async function playPcm16Audio(arrayBuffer, meta) {
      const sampleRate = Number(meta.sample_rate) || 16000;
      const channels = Number(meta.channels) || 1;
      if (!playbackContext || playbackContext.state === "closed") {
        playbackContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate });
      }
      if (playbackContext.state === "suspended") {
        await playbackContext.resume();
      }
      const samples = new Int16Array(arrayBuffer);
      const frames = Math.floor(samples.length / channels);
      const audioBuffer = playbackContext.createBuffer(channels, frames, sampleRate);
      for (let channel = 0; channel < channels; channel++) {
        const output = audioBuffer.getChannelData(channel);
        for (let i = 0; i < frames; i++) {
          output[i] = samples[i * channels + channel] / 32768;
        }
      }
      const source = playbackContext.createBufferSource();
      source.buffer = audioBuffer;
      source.connect(playbackContext.destination);
      pcmSources.add(source);
      source.addEventListener("ended", () => {
        pcmSources.delete(source);
      }, { once: true });
      const startAt = Math.max(playbackContext.currentTime + 0.02, nextPcmPlaybackTime || 0);
      source.start(startAt);
      nextPcmPlaybackTime = startAt + audioBuffer.duration;
      if (!pcmReceiving) {
        log("pcm_raw_audio play bytes=" + arrayBuffer.byteLength + ", duration=" + audioBuffer.duration.toFixed(3) + "s", "ok");
      }
    }

    function scheduleSkipWakeUpIfNeeded() {
      if (!els.conversationMode.checked) return;
      pendingContinueWakeUp = true;
      log("Đã nhận yêu cầu continue_wake_up — lập lịch Skip_WakeUP khi audio kết thúc", "info");
      if (continueWakeUpTimer) return;
      continueWakeUpTimer = setInterval(() => {
        if (urlAudioPlayers.size === 0 && pcmSources.size === 0) {
          log("Audio kết thúc — gửi Skip_WakeUP bây giờ", "info");
          if (socket && socket.readyState === WebSocket.OPEN) {
            sendText("Skip_WakeUP");
          } else {
            log("Không thể gửi Skip_WakeUP: socket chưa mở", "warn");
          }
          pendingContinueWakeUp = false;
          clearInterval(continueWakeUpTimer);
          continueWakeUpTimer = null;
        }
      }, 200);
    }

    function isPlayableAudioUrl(value) {
      return typeof value === "string" && value.trim() && value !== "None" && value !== "null";
    }

    function playUrlAudio(label, url) {
      const audio = new Audio(url);
      urlAudioPlayers.add(audio);
      audio.addEventListener("ended", () => urlAudioPlayers.delete(audio), { once: true });
      audio.addEventListener("error", () => urlAudioPlayers.delete(audio), { once: true });
      audio.play()
        .then(() => log(label + " play " + url, "ok"))
        .catch(error => {
          urlAudioPlayers.delete(audio);
          log("Khong phat duoc " + label + ": " + error.message, "error");
        });
    }

    function stopUrlAudio() {
      let stopped = 0;
      for (const audio of Array.from(urlAudioPlayers)) {
        try {
          audio.pause();
          audio.currentTime = 0;
          stopped += 1;
        } catch {}
        urlAudioPlayers.delete(audio);
      }
      log("Stopped URL audio players: " + stopped, stopped ? "ok" : "info");
    }

    function connectSocket() {
      if (socket && socket.readyState === WebSocket.OPEN) return;

      const url = els.serverUrl.value.trim();
      socket = new WebSocket(url);
      socket.binaryType = "arraybuffer";
      setSocketState("Đang kết nối");

      socket.onopen = () => {
        setConnectedState(true);
        log("Đã kết nối " + url, "ok");
        sendClientConfig();
      };

      socket.onmessage = (event) => {
        if (typeof event.data === "string") {
          try {
            const data = JSON.parse(event.data);
            const isPcmMeta = data.processing_process === "pcm_raw_audio";
            if (!isPcmMeta) {
              log("Server -> " + JSON.stringify(data, null, 2), "server");
            }
            if (isPcmMeta) {
              pendingBinaryAudioMeta = data;
              if (!pcmReceiving) {
                pcmReceiving = true;
                log(
                  "Bắt đầu nhận pcm_raw_audio: bytes=" + data.audio_bytes +
                  ", format=" + data.audio_format +
                  ", rate=" + data.sample_rate +
                  ", channels=" + data.channels,
                  "ok"
                );
              }
              if (pcmReceivingResetTimer) clearTimeout(pcmReceivingResetTimer);
              pcmReceivingResetTimer = setTimeout(() => {
                pcmReceiving = false;
                pcmReceivingResetTimer = null;
              }, 3000);
            } else {
              const hasTtsAudio = isPlayableAudioUrl(data.tts_audio);
              const hasStatusAudio = isPlayableAudioUrl(data.status_audio);
              if (hasTtsAudio) {
                if (els.playTtsAudio.checked) {
                  playUrlAudio("tts_audio", data.tts_audio);
                } else {
                  log("tts_audio skipped " + data.tts_audio, "warn");
                }
              } else if (hasStatusAudio) {
                if (els.playStatusAudio.checked) {
                  playUrlAudio("status_audio", data.status_audio);
                } else {
                  log("status_audio skipped " + data.status_audio, "warn");
                }
              }

              // Server may indicate continue wake-up by a boolean field or by
              // setting processing_process === "continue_wake_up".
              if (data.continue_wake_up || data.processing_process === "continue_wake_up") {
                scheduleSkipWakeUpIfNeeded();
              }
            }
          } catch {
            log("Server -> " + event.data, "server");
          }
        } else {
          if (!pcmReceiving) {
            log("Server gui binary " + event.data.byteLength + " bytes", "server");
          }
          if (pendingBinaryAudioMeta) {
            if (els.playPcmAudio.checked) {
              playPcm16Audio(event.data, pendingBinaryAudioMeta).catch(error => {
                log("Khong phat duoc pcm_raw_audio: " + error.message, "error");
              });
            } else {
              log("pcm_raw_audio skipped bytes=" + event.data.byteLength, "warn");
            }
            pendingBinaryAudioMeta = null;
          }
        }
      };

      socket.onerror = () => {
        log("Lỗi WebSocket", "error");
      };

      socket.onclose = () => {
        log("Socket đã đóng", "warn");
        setConnectedState(false);
        pendingBinaryAudioMeta = null;
        nextPcmPlaybackTime = 0;
        stopRecording(false);
        if (continueWakeUpTimer) {
          clearInterval(continueWakeUpTimer);
          continueWakeUpTimer = null;
          pendingContinueWakeUp = false;
        }
        socket = null;
      };
    }

    function waitForSocketOpen(timeoutMs = 5000) {
      if (socket && socket.readyState === WebSocket.OPEN) {
        return Promise.resolve(true);
      }

      return new Promise((resolve) => {
        const startedAt = Date.now();
        const timer = setInterval(() => {
          if (socket && socket.readyState === WebSocket.OPEN) {
            clearInterval(timer);
            resolve(true);
          } else if (Date.now() - startedAt >= timeoutMs) {
            clearInterval(timer);
            resolve(false);
          }
        }, 50);
      });
    }

    async function ensureSocketConnected() {
      if (!socket || socket.readyState === WebSocket.CLOSED || socket.readyState === WebSocket.CLOSING) {
        connectSocket();
      }
      const ready = await waitForSocketOpen();
      if (!ready) {
        log("Không kết nối được socket để bắt đầu ghi âm", "error");
      }
      return ready;
    }

    function disconnectSocket() {
      stopRecording(true);
      if (socket) {
        socket.close();
      }
    }

    function floatToInt16(floatValue) {
      const clamped = Math.max(-1, Math.min(1, floatValue));
      return clamped < 0 ? clamped * 32768 : clamped * 32767;
    }

    async function startRecording() {
      if (isRecording) return;
      if (!socket || socket.readyState !== WebSocket.OPEN) {
        const ready = await ensureSocketConnected();
        if (!ready) return;
      }

      const sampleRate = Number(els.sampleRate.value) || 16000;
      const chunkSize = Number(els.chunkSize.value) || 512;

      try {

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          log("Trình duyệt không hỗ trợ microphone trên HTTP local", "error");
          log("URL hiện tại: " + window.location.origin, "warn");
          log("Cách khắc phục:", "info");
          log("Dùng Trình Duyệt Chrome Sửa Trong Flag (test tạm):", "info");
          log("Mở: chrome://flags/#unsafely-treat-insecure-origin-as-secure", "info");
          log("Bật: Insecure origins treated as secure", "info");
          log("Thêm URL local này vào ô: "+ window.location.origin, "info");
          log("Sau đó khởi động lại Chrome", "info");
          return;
        }

        mediaStream = await navigator.mediaDevices.getUserMedia({
          audio: {
            channelCount: 1,
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true
          }
        });

        audioContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate });
        sourceNode = audioContext.createMediaStreamSource(mediaStream);
        processorNode = audioContext.createScriptProcessor(chunkSize, 1, 1);

        processorNode.onaudioprocess = (event) => {
          if (!isRecording || !socket || socket.readyState !== WebSocket.OPEN) return;

          const input = event.inputBuffer.getChannelData(0);
          const pcm = new Int16Array(chunkSize);
          for (let i = 0; i < chunkSize; i++) {
            pcm[i] = floatToInt16(input[i] || 0);
          }

          socket.send(pcm.buffer);
          frameCount += 1;
          els.frameCount.textContent = String(frameCount);
        };

        sourceNode.connect(processorNode);
        processorNode.connect(audioContext.destination);
        isRecording = true;
        setMicState("Đang gửi");
        setConnectedState(true);
        log("Đã bật mic, gửi PCM Int16 " + chunkSize + " samples/frame", "ok");
      } catch (error) {
        log("Không mở được microphone: " + error.message, "error");
        stopRecording(false);
      }
    }

    async function startRecordingSession() {
      els.wakeBtn.disabled = true;
      try {
        const socketReady = await ensureSocketConnected();
        if (!socketReady) return;

        if (!isRecording) {
          await startRecording();
        }

        if (!isRecording) {
          log("Mic chưa bật nên không gửi start_recording", "warn");
          return;
        }

        sendText("start_recording");
      } finally {
        els.wakeBtn.disabled = !(socket && socket.readyState === WebSocket.OPEN);
      }
    }

    async function startRecordingWakeUPSession() {
      els.wakeBtn.disabled = true;
      try {
        const socketReady = await ensureSocketConnected();
        if (!socketReady) return;

        if (!isRecording) {
          await startRecording();
        }

        if (!isRecording) {
          log("Mic chưa bật nên không gửi start_recording", "warn");
          return;
        }

        sendText("Skip_WakeUP");
      } finally {
        els.wakeBtn.disabled = !(socket && socket.readyState === WebSocket.OPEN);
      }
    }

    function stopRecording(sendStopCommand = true) {
      if (!isRecording && !audioContext && !mediaStream) return;

      isRecording = false;

      if (processorNode) {
        processorNode.disconnect();
        processorNode.onaudioprocess = null;
        processorNode = null;
      }
      if (sourceNode) {
        sourceNode.disconnect();
        sourceNode = null;
      }
      if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
      }
      if (audioContext) {
        audioContext.close();
        audioContext = null;
      }

      if (sendStopCommand && socket && socket.readyState === WebSocket.OPEN) {
        sendText("stop");
      }

      setMicState("Tắt");
      setConnectedState(Boolean(socket && socket.readyState === WebSocket.OPEN));
      log("Đã tắt mic", "info");
    }

    els.connectBtn.addEventListener("click", connectSocket);
    els.disconnectBtn.addEventListener("click", disconnectSocket);
    els.startBtn.addEventListener("click", startRecording);
    els.stopBtn.addEventListener("click", () => stopRecording(true));
    els.wakeBtn.addEventListener("click", startRecordingSession);
	els.wakeUpBtn.addEventListener("click", startRecordingWakeUPSession);
    els.stopUrlAudioBtn.addEventListener("click", stopUrlAudio);
    els.clearBtn.addEventListener("click", () => {
      els.log.textContent = "";
      frameCount = 0;
      els.frameCount.textContent = "0";
    });

    els.sessionId.value = makeSessionId();
      els.helpBtn.addEventListener("click", () => {
        els.helpText.classList.toggle("d-none");
        els.currentUrl.textContent = window.location.href;
      });
  </script>

</body>

</html>