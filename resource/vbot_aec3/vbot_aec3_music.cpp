// Small C ABI for webrtc-audio-processing 1.3 (its full echo controller is AEC3).
#include <cstdint>
#include <array>
#include <algorithm>
#include <cmath>
#include <memory>
#include "modules/audio_processing/include/audio_processing.h"
#include "modules/audio_processing/aec3/echo_canceller3.h"

class MusicEchoFactory : public webrtc::EchoControlFactory {
 public:
  std::unique_ptr<webrtc::EchoControl> Create(int rate, int render, int capture) override {
    webrtc::EchoCanceller3Config config;
    config.filter.export_linear_aec_output = true;
    return std::make_unique<webrtc::EchoCanceller3>(config, rate, render, capture);
  }
};

struct Processor {
  rtc::scoped_refptr<webrtc::AudioProcessing> apm;
  webrtc::StreamConfig stream{16000, 1};
};
extern "C" {
int vbot_aec_abi() { return 1; }
// Independent post-AEC processor: digital AGC never touches the ALSA mixer.
void* vbot_mic_create(int ns, int agc, int target, int gain, int limiter, int highpass) {
  if (ns < 0 || ns > 4 || target < 0 || target > 31 || gain < 0 || gain > 90)
    return nullptr;
  try {
    auto p = std::make_unique<Processor>();
    p->apm = webrtc::AudioProcessingBuilder().Create();
    if (!p->apm) return nullptr;
    webrtc::AudioProcessing::Config cfg;
    cfg.echo_canceller.enabled = false;
    cfg.high_pass_filter.enabled = highpass != 0;
    cfg.noise_suppression.enabled = ns != 0;
    using NS = webrtc::AudioProcessing::Config::NoiseSuppression;
    const NS::Level levels[] = {NS::kLow, NS::kLow, NS::kModerate, NS::kHigh, NS::kVeryHigh};
    cfg.noise_suppression.level = levels[ns];
    cfg.gain_controller1.enabled = agc != 0;
    cfg.gain_controller1.mode = webrtc::AudioProcessing::Config::GainController1::kAdaptiveDigital;
    cfg.gain_controller1.analog_gain_controller.enabled = false;
    cfg.gain_controller1.target_level_dbfs = target;
    cfg.gain_controller1.compression_gain_db = gain;
    cfg.gain_controller1.enable_limiter = limiter != 0;
    cfg.gain_controller2.enabled = false;
    p->apm->ApplyConfig(cfg);
    if (p->apm->Initialize() != 0) return nullptr;
    return p.release();
  } catch (...) { return nullptr; }
}
void* vbot_aec_create() {
  try {
    auto p = std::make_unique<Processor>();
    webrtc::AudioProcessingBuilder builder;
    builder.SetEchoControlFactory(std::make_unique<MusicEchoFactory>());
    p->apm = builder.Create();
    if (!p->apm) { return nullptr; }
    webrtc::AudioProcessing::Config cfg;
    cfg.echo_canceller.enabled = true;
    cfg.echo_canceller.mobile_mode = false;
    cfg.echo_canceller.export_linear_aec_output = true;
    cfg.high_pass_filter.enabled = true;
    cfg.noise_suppression.enabled = false;
    cfg.gain_controller1.enabled = false;
    cfg.gain_controller2.enabled = false;
    p->apm->ApplyConfig(cfg);
    if (p->apm->Initialize() != 0) { return nullptr; }
    return p.release();
  } catch (...) { return nullptr; }
}
int vbot_aec_render(void* handle, const int16_t* pcm) {
  try {
    auto* p = static_cast<Processor*>(handle);
    int16_t out[160];
    return p->apm->ProcessReverseStream(pcm, p->stream, p->stream, out);
  } catch (...) { return -100; }
}
// Optional extension: ABI 1 callers without this option remain compatible.
int vbot_aec_set_noise_suppression(void* handle, int level) {
  if (!handle || level < 0 || level > 4) { return -1; }
  try {
    auto* p = static_cast<Processor*>(handle);
    auto cfg = p->apm->GetConfig();
    cfg.noise_suppression.enabled = level != 0;
    using NS = webrtc::AudioProcessing::Config::NoiseSuppression;
    switch (level) {
      case 1: cfg.noise_suppression.level = NS::kLow; break;
      case 2: cfg.noise_suppression.level = NS::kModerate; break;
      case 3: cfg.noise_suppression.level = NS::kHigh; break;
      case 4: cfg.noise_suppression.level = NS::kVeryHigh; break;
    }
    p->apm->ApplyConfig(cfg);
    return 0;
  } catch (...) { return -100; }
}
int vbot_aec_capture(void* handle, const int16_t* pcm, int16_t* out, int delay) {
  if (!handle || !pcm || !out) return -1;
  try {
    auto* p = static_cast<Processor*>(handle);
    // APM 1.3 may not write dest when all capture modules are disabled.
    std::copy_n(pcm, 160, out);
    int rc = p->apm->set_stream_delay_ms(delay);
    return rc ? rc : p->apm->ProcessStream(pcm, p->stream, p->stream, out);
  } catch (...) { return -100; }
}
void vbot_aec_destroy(void* handle) { delete static_cast<Processor*>(handle); }
// WebRTC Audio Processing 1.3 returns FloatS16 here (verified in its source),
// unlike newer upstream normalized-float APIs. No capture call here;
// read immediately after the normal ProcessStream under the Python AEC lock.
int vbot_aec_get_linear(void* handle, int16_t* out) {
  if (!handle || !out) return -1;
  try {
    std::array<float, 160> linear;
    auto* p = static_cast<Processor*>(handle);
    if (!p->apm->GetLinearAecOutput(rtc::ArrayView<std::array<float, 160>>(&linear, 1))) return -2;
    for (size_t i = 0; i < linear.size(); ++i) {
      if (!std::isfinite(linear[i])) return -3;
      out[i] = static_cast<int16_t>(std::lround(std::max(-32768.f, std::min(32767.f, linear[i]))));
    }
    return 0;
  } catch (...) { return -100; }
}
}
