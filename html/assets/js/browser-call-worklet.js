/* Test transport: mono signed little-endian PCM, 16 kHz, 100 ms per request. */
class VBotCallPCM extends AudioWorkletProcessor {
    constructor() {
        super();
        this.phase = 0;this.sum = 0;this.count = 0;
        this.capture = new DataView(new ArrayBuffer(3200));this.position = 0;
        this.playback = [];this.playPosition = 0;
        this.port.onmessage = event => {
            const view = new DataView(event.data);
            for (let i = 0; i + 1 < view.byteLength; i += 2) this.playback.push(view.getInt16(i, true) / 32768);
            if (this.playback.length > 6400) {
                this.playback.splice(0, this.playback.length - 3200);
                this.playPosition = 0;
            }
        };
    }
    process(inputs, outputs) {
        const input = inputs[0]?.[0];
        const output = outputs[0][0];
        for (let i = 0; i < output.length; i++) {
            this.sum += input?.[i] || 0;this.count++;
            this.phase += 16000;
            if (this.phase >= sampleRate) {
                const sample = Math.max(-1, Math.min(1, this.sum / this.count));
                this.capture.setInt16(this.position * 2, Math.round(sample * 32767), true);
                this.position++;this.phase -= sampleRate;this.sum = 0;this.count = 0;
                if (this.position === 1600) {
                    this.port.postMessage(this.capture.buffer, [this.capture.buffer]);
                    this.capture = new DataView(new ArrayBuffer(3200));this.position = 0;
                }
            }
            const at = Math.floor(this.playPosition), fraction = this.playPosition - at;
            output[i] = this.playback.length > at + 1
                ? this.playback[at] * (1 - fraction) + this.playback[at + 1] * fraction : 0;
            this.playPosition += 16000 / sampleRate;
        }
        const consumed = Math.min(Math.floor(this.playPosition), this.playback.length);
        this.playback.splice(0, consumed);
        this.playPosition = this.playback.length ? this.playPosition - consumed : 0;
        return true;
    }
}
registerProcessor('vbot-call-pcm', VBotCallPCM);
