"""Local authenticated HTTP checks; never print credentials or send LAN WoL."""
import hashlib
from http.cookiejar import CookieJar
import json
from pathlib import Path
import re
import socket
import sys
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path('/home/pi/VBot_Offline')
sys.path.insert(0, str(ROOT))
import Wake_On_LAN as wol


def main():
    base = 'http://127.0.0.1/'
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(CookieJar()))
    config = json.loads((ROOT/'Config.json').read_text(encoding='utf-8-sig'))
    def request(path, data=None):
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        with client.open(base+path, data=body, timeout=15) as response:
            return response.read().decode('utf-8'), response.geturl()
    login, _ = request('Login.php')
    if config['contact_info']['user_login']['active']:
        token = re.search(r'name="csrf_token"\s+value="([a-f0-9]+)"', login).group(1)
        digest = hashlib.sha256(config['contact_info']['user_login']['user_password'].encode()).hexdigest()
        _, url = request('Login.php', {'csrf_token':token, 'token_password':digest})
        assert not url.endswith('Login.php'), 'Login did not complete'
    html, url = request('Wake_On_LAN.php')
    assert url.endswith('Wake_On_LAN.php') and 'id="wol-settings"' in html and 'wake-on-lan.js' in html
    csrf = re.search(r'window\.vbotWolCsrf\s*=\s*"([a-f0-9]+)"', html).group(1)
    shared_csrf = re.search(r'window\.VBOT_CSRF_TOKEN\s*=\s*"([a-f0-9]+)"', html).group(1)
    assert shared_csrf == csrf, 'Shared WebUI controls need the same CSRF token'
    api = 'includes/php_ajax/Wake_On_LAN.php'
    raw, _ = request(api, {'action':'list', 'csrf_token':csrf})
    current = json.loads(raw)
    assert current['success'] is True
    # Save exactly the existing devices, never insert a physical PC target.
    raw, _ = request(api, {'action':'save', 'csrf_token':csrf,
                          'revision':current['revision'], 'data':json.dumps(current['data'])})
    assert json.loads(raw)['success'] is True
    assert wol.load() == current['data']
    try:
        request(api, {'action':'save', 'data':'{}'})
        raise AssertionError('Missing CSRF should be rejected')
    except urllib.error.HTTPError as error:
        assert error.code == 403
    for kind in ('bluetooth', 'airplay'):
        raw, _ = request('includes/php_ajax/Scanner.php?check_version='+kind)
        result = json.loads(raw)
        assert result['success'] is True and result['version']
    scheduler, _ = request('Scheduler.php')
    assert 'system-task-schedules-container' in scheduler
    routines_path = ROOT/'resource/voice_routines/voice_routines.json'
    routines = json.loads(routines_path.read_text())['routines'] if routines_path.exists() else []
    for routine in routines:
        assert 'vbot_action:routine:'+routine['id'] in scheduler
    with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as receiver:
        receiver.bind(('127.0.0.1', 0)); receiver.settimeout(1)
        test = dict(version=1, active=True, devices=[dict(id='local_probe', name='Local probe',
                    mac='02:11:22:33:44:55', broadcast='127.0.0.1', port=receiver.getsockname()[1],
                    enabled=True, aliases=[])])
        wol.send('local_probe', test)
        for _ in range(3):
            packet, _ = receiver.recvfrom(256)
            assert packet == b'\xff'*6 + bytes.fromhex('021122334455')*16
    request('Login.php?logout=1')
    print(json.dumps(dict(webui=True, login=True, csrf=True, save_unchanged=True,
                          version_scanner=True, scheduler_routines=len(routines),
                          wol_udp_loopback=True, physical_pc_tested=False)))


if __name__ == '__main__':
    main()
