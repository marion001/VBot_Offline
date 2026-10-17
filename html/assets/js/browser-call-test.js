(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    let context, node, source, stream, token, timer, busy = false, generation = 0, ending = null;
    let chunks = [], sequence = 0, muted = false, lastCapture = 0;
    const status = text => { $('browserCallStatus').textContent = text; };
    async function api(payload, keepalive = false) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), payload.action === 'start' ? 25000 : 5000);
        try {
            const response = await fetch('includes/php_ajax/Browser_Call_Test.php', {
                method: 'POST', credentials: 'same-origin', keepalive,
                headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''},
                body: new URLSearchParams({csrf: window.browserCallCsrf, payload: JSON.stringify(payload)}),
                signal: controller.signal
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw Error(result.message || 'Call failed');
            return result;
        } finally { clearTimeout(timeout); }
    }
    function base64(buffer) {
        return btoa(String.fromCharCode(...new Uint8Array(buffer)));
    }
    function bytes(value) {
        return Uint8Array.from(atob(value), c => c.charCodeAt(0)).buffer;
    }
    function end(message = 'Đã kết thúc.') {
        if (ending) return ending;
        generation++;
        const oldToken = token;token = null;
        clearTimeout(timer);timer = null;chunks = [];
        if (source) { source.disconnect();source = null; }
        if (node) { node.disconnect();node.port.onmessage = null;node = null; }
        if (stream) { stream.getTracks().forEach(track => track.stop());stream = null; }
        if (context) { context.close().catch(() => {});context = null; }
        $('browserHangup').disabled = $('browserMute').disabled = true;
        $('browserDial').disabled = true;
        $('browserMute').textContent = 'Tắt mic trình duyệt';muted = false;
        status(message);
        ending = (async () => {
            if (oldToken) { try { await api({action: 'end', token: oldToken}, true); } catch (_) { /* Server watchdog releases disconnected clients. */ } }
        })().finally(() => { ending = null;$('browserDial').disabled = busy || !!token; });
        return ending;
    }
    async function exchange(attempt) {
        if (attempt !== generation || !token) return;
        if (!chunks.length) {
            if (performance.now() - lastCapture > 5000) { await end('Microphone trình duyệt đã ngừng cung cấp âm thanh.');return; }
            timer = setTimeout(() => exchange(attempt), 20);return;
        }
        const pcm = chunks.shift();
        try {
            const start = performance.now();
            const result = await api({action: 'exchange', token, sequence: sequence++,
                pcm: base64(muted ? new ArrayBuffer(3200) : pcm)});
            if (attempt !== generation) return;
            node.port.postMessage(bytes(result.pcm));
            $('browserCallStats').textContent = JSON.stringify({transport: 'HTTPS PCM',
                rate: 16000, block_ms: 100, request_ms: Math.round(performance.now() - start),
                sequence: result.sequence, queued_blocks: chunks.length}, null, 2);
            timer = setTimeout(() => exchange(attempt), 0);
        } catch (error) { if (attempt === generation) await end(error.message); }
    }
    $('browserDial').onclick = async () => {
        if (busy || token || ending) return;
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia || !window.AudioWorkletNode) {
            status('Cần HTTPS được tin cậy và trình duyệt hỗ trợ AudioWorklet.');return;
        }
        busy = true;const attempt = ++generation;
        $('browserDial').disabled = true;
        $('browserHangup').disabled = false;
        status('Đang xin quyền microphone…');
        try {
            context = new AudioContext();
            if (context.sampleRate < 16000) throw Error('Thiết bị âm thanh cần hỗ trợ ít nhất 16 kHz.');
            await context.resume();
            if (attempt !== generation) return;
            const captured = await navigator.mediaDevices.getUserMedia({audio: {
                echoCancellation: true, noiseSuppression: true, autoGainControl: true, channelCount: 1
            }, video: false});
            if (attempt !== generation) { captured.getTracks().forEach(track => track.stop());return; }
            stream = captured;
            stream.getAudioTracks().forEach(track => {
                track.onended = () => { if (attempt === generation) end('Microphone trình duyệt đã ngắt.'); };
            });
            await context.audioWorklet.addModule('assets/js/browser-call-worklet.js');
            if (attempt !== generation) return;
            node = new AudioWorkletNode(context, 'vbot-call-pcm', {outputChannelCount: [1]});
            node.port.onmessage = event => {
                lastCapture = performance.now();
                chunks.push(event.data);
                if (chunks.length > 2) chunks.shift();
            };
            source = context.createMediaStreamSource(stream);
            source.connect(node);node.connect(context.destination);
            status('Đang kết nối tới loa…');
            const answer = await api({action: 'start'});
            if (attempt !== generation) { await api({action: 'end', token: answer.token}, true);return; }
            token = answer.token;sequence = 0;chunks = [];lastCapture = performance.now();
            $('browserMute').disabled = false;
            status('Đang gọi.');
            exchange(attempt);
        } catch (error) { if (attempt === generation) await end(error.message); }
        finally { busy = false;$('browserDial').disabled = !!token || !!ending; }
    };
    $('browserHangup').onclick = () => end();
    $('browserMute').onclick = () => {
        muted = !muted;
        $('browserMute').textContent = muted ? 'Bật mic trình duyệt' : 'Tắt mic trình duyệt';
    };
    window.addEventListener('pagehide', () => end());
})();
