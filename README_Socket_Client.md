# VBot Streaming Socket Protocol

Tài liệu này mô tả cơ chế Client kết nối tới máy chủ VBot Streaming Server khi `connection_protocol` là `socket`.

File Server liên quan: `Streaming`

## 1. Cấu hình Server

Mỗi Client sẽ tự khai báo `working_mode` khi kết nối thành công
Cấu Hình mẫu Config.json trong VBot Server

```json
{
  "api": {
    "streaming_server": {
      "active": true,
      "connection_protocol": "socket",
      "protocol": {
        "socket": {
          "port": 5003,
          "maximum_recording_time": 5,
          "maximum_client_connected": 3,
          "source_stt": "stt_ggcloud",
          "select_wakeup": "snowboy",
          "client_conversation_mode": true,
          "music_playback_on_client": true
        }
      }
    }
  }
}
```

Client kết nối tới:

```text
ws://<IP_VBOT>:<port>
```

Ví dụ:

```text
ws://192.168.1.10:5003
```

## 2. Kiểu dữ liệu qua WebSocket

Server và client trao đổi 2 kiểu message:

| Kiểu | Hướng | Ý nghĩa |
| --- | --- | --- |
| Text JSON | Client -> Server | Khai báo `session_id`, `working_mode` |
| Text command | Client -> Server | Lệnh `start_recording`, `Skip_WakeUP`, `stop` |
| Binary | Client -> Server | PCM raw microphone gửi lên STT/wakeup |
| Text JSON | Server -> Client | Trạng thái xử ý, transcript, metadata audio |
| Binary | Server -> Client | PCM raw audio để client phát realtime |

## 3. Kết nối và khai báo Client

Khi WebSocket vừa kết nối, server trả:

```json
{
  "vbot_client_id": "('192.168.1.20', 54321)",
  "message": "Đã kết nối VBot Socket Server"
}
```

Sau đó client nên gửi JSON cấu hình:

```json
{
  "session_id": "client_phong_khach",
  "working_mode": "main_processing"
}
```

Server trả về:

```json
{
  "vbot_client_id": "client_phong_khach",
  "working_mode": "main_processing",
  "message": "Đã nhận cấu hình client"
}
```

`working_mode` hỗ trợ các tham số:

| Mode | Ý Nghĩa |
| --- | --- |
| `main_processing` | Xử lý đầy đủ qua VBot. nếu assistant trả PCM raw, server stream `pcm_raw_audio` về Client. |
| `chatbot` | gửi transcript vào chatbot và trả text/audio kết quả tùy dữ liệu |
| `stt_to_tts` | Hiện tại chỉ trả text STT về Client, Không tạo TTS. Alias `stt_to_text` cũng được chấp nhận. |

Để tương thích Client cơ chế cũ, nếu Client gửi text thường không phải lệnh đặc biệt và không phải JSON, server xem text đó là `session_id`.

## 4. Lệnh text Client gửi

### Bắt đầu ghi âm không cần WakeUP HotWord

Client gửi 1 trong các tham số text sau:

```text
Skip_WakeUP
start
start_recording
```

Nếu Server rảnh (ở chế độ chờ), server sẽ trả:

```json
{
  "processing_process": "wake_word_detected",
  "wake_word_detected": true,
  "status_audio": "http://192.168.1.10/assets/sound/default/ding.mp3",
  "message": "Đã được đánh thức!"
}
```

Nếu server đang xử lý dữ liệu client khác:

```json
{
  "processing_process": "waiting_to_wake_up",
  "waiting_to_wake_up": true,
  "status_audio": "http://192.168.1.10/assets/sound/default/dong.mp3",
  "message": "Yêu cầu bị từ chối: Có client khác đang xử lý"
}
```

### Dừng ghi âm

Client gửi:

```text
stop
```

Server kết thúc audio queue hiện tại và đưa audio đã nhận sang STT.

`status_audio` là tham số riêng cho kết nối socket. kết nối UDP không thêm trường giá trị này.

## 5. Binary audio client gửi lên Server

Client gửi microphone bằng WebSocket binary:

```text
PCM raw
Signed 16-bit little-endian
Mono
Khuyến nghị 16000 Hz
Frame wakeup: 512 samples = 1024 bytes
```

Khi chưa recording, server chỉ xử lý binary frame đúng `1024` bytes để chạy wake word.

Khi đã recording, server đưa các binary chunk vào STT. Chunk nên đều để giảm độ trễ.

## 6. Luồng hoạt động bỏ qua wake word

1. Client kết nối WebSocket.
2. Server trả message kết nối.
3. Client gửi JSON cấu hình gồm `session_id`, `working_mode`.
4. Client gửi `start_recording`.
5. Server trả `wake_word_detected`.
6. Server trả `recording`.
7. Client gửi binary PCM microphone.
8. Client gửi `stop`, hoặc server tự dừng sau giá trị `maximum_recording_time` (giây) được cấu hình ở Config.json Server
9. Server STT và trả `data_processing`.
10. Server trả nội dung kết quả dữ liệu theo cơ chế `working_mode`.
11. Nếu có PCM raw audio phản hồi, server gửi realtime từng cặp `pcm_raw_audio` metadata + binary PCM.

## 7. JSON server trả về

### Đang ghi âm

```json
{
  "processing_process": "recording",
  "recording_streaming": true,
  "message": "Đang thu âm..."
}
```

### Không có giọng nói

```json
{
  "processing_process": "waiting_to_wake_up",
  "waiting_to_wake_up": true,
  "status_audio": "http://192.168.1.10/assets/sound/default/dong.mp3",
  "message": "Không có giọng nói được truyền vào, đang chờ được đánh thức"
}
```

### Đã có transcript, bắt đầu xử lý

```json
{
  "processing_process": "data_processing",
  "transcript_normed": "bật đèn phòng khách",
  "message": "Đang xử lý dữ liệu"
}
```

### Kết quả `main_processing`, tiếp tục hỏi

```json
{
  "processing_process": "continue_wake_up",
  "tts_audio": "http://192.168.1.10/assets/sound/TTS_Audio/file.mp3",
  "assistant_text": "Đã bật đèn phòng khách",
  "response_text": "Đã bật đèn phòng khách",
  "message": "Đã xử lý xong dữ liệu, tiếp tục được đánh thức"
}
```

### Kết quả `main_processing`, quay về chờ wake word

```json
{
  "processing_process": "waiting_to_wake_up",
  "status_audio": "http://192.168.1.10/assets/sound/default/dong.mp3",
  "tts_audio": "http://192.168.1.10/assets/sound/TTS_Audio/file.mp3",
  "assistant_text": "Đã bật đèn phòng khách",
  "response_text": "Đã bật đèn phòng khách",
  "message": "Đã xử lý xong dữ liệu, đang chờ được đánh thức"
}
```

### Kết quả `chatbot`

```json
{
  "processing_process": "chatbot_response",
  "tts_audio": "http://192.168.1.10/assets/sound/TTS_Audio/file.mp3",
  "assistant_text": "Nội dung chatbot trả về",
  "response_text": "Nội dung chatbot trả về",
  "message": "Đã xử lý xong dữ liệu chatbot"
}
```

### kết quả `stt_to_tts`

Mode này chỉ trả text STT, không tạo TTS:

```json
{
  "processing_process": "stt_to_tts_response",
  "tts_audio": null,
  "assistant_text": "nội dung stt",
  "response_text": "nội dung stt",
  "message": "Đã chuyển đổi STT sang text"
}
```

### Lỗi

```json
{
  "processing_process": "error",
  "message": "Lỗi xử lý dữ liệu trên server"
}
```

## 8. Realtime `pcm_raw_audio`

Khi `working_mode` là `main_processing`, nếu assistant trả về audio PCM raw, server sẽ stream realtime về client. Mỗi chunk gồm 2 message liên tiếp:

1. Text JSON metadata:

```json
{
  "processing_process": "pcm_raw_audio",
  "audio_format": "pcm_s16le",
  "sample_rate": 16000,
  "channels": 1,
  "sample_width": 2,
  "audio_bytes": 1920,
  "streaming": true,
  "message": "Dữ liệu âm thanh PCM raw"
}
```

2. Binary frame ngay sau đó, dộ dài bằng `audio_bytes`.

Binary frame là:

```text
PCM raw
Signed 16-bit little-endian
Mono
Sample rate theo metadata, mặc định 16000 Hz
```

Client nên xử lý như sau:

1. Khi nhận JSON có `processing_process == "pcm_raw_audio"`, luu metadata này.
2. Binary frame tiếp theo là audio của metadata vừa nhận.
3. Kiểm tra cấu hình client có muốn phát audio hay không.
4. Nếu có, phát PCM theo `sample_rate`, `channels`, `sample_width`.
5. Nếu không, bỏ qua binary frame nhưng vẫn nên log `audio_bytes`.

## 9. Tham số client gửi

### JSON cấu hình client

| Field | Kiểu | Bắt buộc | Ý Nghĩa |
| --- | --- | --- | --- |
| `session_id` | string | Khuyến nghị | Tên định danh client. |
| `working_mode` | string | Khuyến nghị | `main_processing`, `chatbot`, `stt_to_tts` hoặc `stt_to_text`. |
| `vbot_client_id` | string | không | Alias của `session_id`. |
| `client_id` | string | Không | Alias của `session_id`. |

### Text command

| Command | Ý nghĩa |
| --- | --- |
| `Skip_WakeUP` | bắt đầu recording không cần wake word. |
| `start` | Bắt đầu recording không cần wake word. |
| `start_recording` | bắt đầu recording không cần wake word. |
| `stop` | Kết thúc recording hiện tại. |

### Binary microphone

| Tham số | Giá trị |
| --- | --- |
| Format | PCM raw |
| Encoding | signed int16 little-endian |
| Channels | 1 |
| Sample rate | Nên khớp STT, thường là 16000 |
| Wake frame | 512 samples, 1024 bytes |

## 10. Tham số server trả về

| Field | Có trong | Ý nghĩa |
| --- | --- | --- |
| `processing_process` | Hầu hết JSON | Trạng thái hiện tại của server. |
| `recording_streaming` | `recording` | Server đang nhận audio recording. |
| `wake_word_detected` | `wake_word_detected` | Server đã bắt đầu recording. |
| `waiting_to_wake_up` | `waiting_to_wake_up` | Server quay về trạng thái chờ. |
| `transcript_normed` | `data_processing` | Text STT đã nhận. |
| `tts_audio` | Kết quả xử lý | URL/path audio nếu có. Có thể là `null` hoặc `"None"`. |
| `status_audio` | Trạng thái wake/wait | URL đầy đủ của âm thanh trạng thái, ví dụ ding/dong. |
| `assistant_text` | Kết quả xử lý | Text assistant trả về. |
| `response_text` | Kết quả xử lý | Text response để client hiển thị. |
| `audio_format` | `pcm_raw_audio` | Định dạng PCM raw, hiện tại `pcm_s16le`. |
| `sample_rate` | `pcm_raw_audio` | Sample rate của binary frame tiếp theo. |
| `channels` | `pcm_raw_audio` | Số kênh audio. |
| `sample_width` | `pcm_raw_audio` | Số byte mỗi sample. |
| `audio_bytes` | `pcm_raw_audio` | Độ dài binary frame tiếp theo. |
| `streaming` | `pcm_raw_audio` | `true` nếu audio được gửi realtime theo chunk. |

Nếu `smart_config.smart_wakeup.wakeup_reply.active = true`, `status_audio` của `wake_word_detected` sẽ là một file ngẫu nhiên trong danh sách `wakeup_reply.sound_file` đang active. Nếu tắt `wakeup_reply`, server dùng âm thanh mặc định ding.mp3 `Lib.Sound_Start`.

Ngoại lệ: nếu client chủ động gửi `Skip_WakeUP`, `status_audio` luôn là âm thanh mặc định `Lib.Sound_Start` (`ding.mp3`), không dùng wakeup_reply.

## 11. Ví dụ client JavaScript nhận `pcm_raw_audio`

```javascript
let pendingPcmMeta = null;
let audioContext = null;
let nextPlayTime = 0;

async function playPcm16(arrayBuffer, meta, shouldPlay) {
  if (!shouldPlay) return;

  const sampleRate = Number(meta.sample_rate) || 16000;
  const channels = Number(meta.channels) || 1;
  audioContext ||= new AudioContext({ sampleRate });

  if (audioContext.state === "suspended") {
    await audioContext.resume();
  }

  const samples = new Int16Array(arrayBuffer);
  const frames = Math.floor(samples.length / channels);
  const audioBuffer = audioContext.createBuffer(channels, frames, sampleRate);

  for (let channel = 0; channel < channels; channel++) {
    const output = audioBuffer.getChannelData(channel);
    for (let i = 0; i < frames; i++) {
      output[i] = samples[i * channels + channel] / 32768;
    }
  }

  const source = audioContext.createBufferSource();
  source.buffer = audioBuffer;
  source.connect(audioContext.destination);

  const startAt = Math.max(audioContext.currentTime + 0.02, nextPlayTime || 0);
  source.start(startAt);
  nextPlayTime = startAt + audioBuffer.duration;
}

socket.onmessage = async (event) => {
  if (typeof event.data === "string") {
    const data = JSON.parse(event.data);
    if (data.processing_process === "pcm_raw_audio") {
      pendingPcmMeta = data;
      console.log("pcm_raw_audio meta", data);
    }
    return;
  }

  if (pendingPcmMeta) {
    const shouldPlay = document.getElementById("playPcmAudio").checked;
    console.log("pcm_raw_audio binary bytes", event.data.byteLength, "play", shouldPlay);
    await playPcm16(event.data, pendingPcmMeta, shouldPlay);
    pendingPcmMeta = null;
  }
};
```

## 12. Lưu ý

- Client phải dùng WebSocket, không phải TCP raw socket.
- Client nên gửi JSON cấu hình ngay sau khi kết nối.
- `working_mode` là theo từng client, không cấu hình trong Config.json của server `protocol.socket`.
- Nếu nhận `pcm_raw_audio`, binary frame ngay sau đó là audio PCM tương ứng.
- Nếu client không muốn phát audio, vẫn nên đọc binary frame và bỏ qua để giữ thứ tự message.
- WebSocket server dùng ping `ping_interval=20`, `ping_timeout=10`; client nên dùng thư viện WebSocket chuẩn để tự dong pong (kiểm tra kết nối)

## 13. Cấu hình liên quan

| Key | Ý nghĩa |
| --- | --- |
| `port` | Cổng WebSocket server. |
| `maximum_recording_time` | Thời gian thu âm tối đa cho mỗi lượt nói. |
| `maximum_client_connected` | Số client WebSocket kết nối tối đa. |
| `source_stt` | Nguồn xử lý STT: `stt_ggcloud`, `stt_ggcloud_v2`, `stt_default`. |
| `select_wakeup` | Engine wake word: `snowboy` hoặc `porcupine`. |
| `client_conversation_mode` | Cho phép client tiếp tục hội thoại sau khi xử lý xong. |
| `music_playback_on_client` | Cho phép gửi URL media về client khi xử lý lệnh phát nhạc. |
| `audio_queue_maxsize` | Tùy chọn, kích thước queue audio. |
| `ws_open_timeout` | Tùy chọn, timeout mở kết nối tới STT websocket phụ. |
| `ws_recv_timeout` | Tùy chọn, timeout nhận phản hồi từ STT websocket phụ. |
| `thread_join_timeout` | Tùy chọn, timeout join thread STT. |

`time_remove_inactive_clients` chỉ dùng cho UDP, không áp dụng cho chế độ socket.
