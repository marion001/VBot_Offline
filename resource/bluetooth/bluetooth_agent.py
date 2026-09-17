#!/usr/bin/env python3
# VBot Bluetooth Agent revision: 8

'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import dbus
import dbus.service
import dbus.mainloop.glib
from gi.repository import GLib
import subprocess
import time
import os
import shlex
import signal
import threading
import json
import re
from datetime import datetime
from collections import OrderedDict
from pathlib import Path

#Tên DBus của BlueZ và đường dẫn Agent
BUS_NAME = "org.bluez"
AGENT_PATH = "/com/vbot/agent"
DEFAULT_CONFIG_PATH = "/home/pi/VBot_Offline/Config.json"
DEFAULT_BLUETOOTH_ADAPTER = "hci0"

#Đọc adapter từ Config.json, mọi dữ liệu thiếu/sai đều trả về hci0
def load_bluetooth_adapter(config_path=None):
    path = config_path or os.environ.get("VBOT_CONFIG_PATH", DEFAULT_CONFIG_PATH)
    try:
        with open(path, "r", encoding="utf-8-sig") as config_file:
            config_data = json.load(config_file)
        adapter = config_data.get("bluetooth", {}).get("adapter", DEFAULT_BLUETOOTH_ADAPTER)
        if isinstance(adapter, str):
            adapter = adapter.strip().lower()
            if re.fullmatch(r"hci\d+", adapter):
                return adapter
    except (OSError, ValueError, TypeError, AttributeError):
        pass
    return DEFAULT_BLUETOOTH_ADAPTER

BLUETOOTH_ADAPTER = os.environ.get("VBOT_BLUETOOTH_ADAPTER", "").strip().lower()
if not re.fullmatch(r"hci\d+", BLUETOOTH_ADAPTER):
    BLUETOOTH_ADAPTER = load_bluetooth_adapter()
ENABLE_PASSKEY_AUTH = False             # VBot không có bàn phím/màn hình: dùng Just Works (NoInputNoOutput)
DEFAULT_PIN_CODE = "0000"
DEFAULT_PASSKEY = 0
BT_AUDIO_SUSPEND_FILE = "/tmp/vbot_bt_audio_suspended"

#Thời gian cấu hình ghép đôi
PAIRING_VALIDATE_DELAY = 1              # giây: chu kỳ kiểm tra pairing, đảm bảo MAX_WAIT chính xác
PAIRING_MAX_WAIT = 60                   # giây: đủ thời gian xác nhận pairing trên điện thoại
UNPAIRED_CLEANUP_DELAY = 2              # dọn object tạm để người dùng có thể pairing lại ngay
PAIRED_RECONNECT_DELAY = 2              # chờ BlueZ hoàn tất lưu khóa trước khi nối lại profile
PAIRED_RECONNECT_MAX_ATTEMPTS = 3       # agent được phép chủ động thử kết nối lại tối đa khi pairing vừa thành công nhưng kết nối bị rớt sớm
AUDIO_START_DELAY = 3                   # chờ pairing/A2DP ổn định trước khi mở BlueALSA PCM
AUDIO_RESTART_COOLDOWN = 4              # tránh health-check restart ngay khi process vừa được tạo
CONNECTED_ACTIVATION_DELAY = 3          # xác nhận Connected=True ổn định trước khi công nhận kết nối paired cũ
DISCONNECT_CONFIRM_DELAY = 1            # xác nhận mất ACL thật trước khi tự reconnect sau pairing
POST_PAIR_RECONNECT_WINDOW = 20         # chỉ bảo vệ ngắt bất thường trong 20s đầu sau pairing
POST_PAIR_STABLE_DELAY = 10             # link Bluetooth duy trì ổn định 10s thì tắt bảo vệ auto-reconnect
SOFTVOLUME_RETRY_DELAY = 2              # Nếu agent chưa bật được SoftVolume=True, nó sẽ chờ 2 giây rồi thử lại
SOFTVOLUME_MAX_ATTEMPTS = 3             # agent sẽ thử bật SoftVolume=True tối đa 3 lần nếu lần đầu chưa thành công
DEFAULT_SOFTVOLUME_PERCENT = None       # mức dự phòng an toàn, chỉ áp dụng 1 lần cho mỗi lần kết nối Bluetooth: None = auto,  đặt âm lượng: 0-100
A2DP_VOLUME_MAX = 127                   # org.bluealsa.PCM1.Volume dùng dải 0..127 cho A2DP
COMMAND_TIMEOUT = 10                    #lệnh hệ thống mà agent chạy qua subprocess được phép chạy tối đa 10 giây

#Ngưỡng timeout disconnect để quyết định xóa thiết bị stale
DISCONNECT_REMOVE_THRESHOLD = 10        # giây: nếu disconnect sớm hơn mức này thì remove
ENABLE_AUDIO_AUTOPLAY = False           # True để tự động nhận âm thanh ở kết nối mới khi thiết bị mới kết nối, False để tắt sẽ nhận âm thanh ở thiết bị kết nối đầu tiên

#nhớ trạng thái tạm thời
pairing_devices = set()                 # thiết bị đang trong flow pairing
pairing_started_at = {}                 # thời điểm bắt đầu pairing cho mỗi MAC
paired_completed_at = {}                # thời điểm pairing vừa hoàn tất, dùng để bảo vệ rớt kết nối ngay sau pairing
last_connected_at = {}                  # thời điểm connect thành công lần cuối của mỗi MAC
pending_unpaired_cleanup = {}           # MAC -> GLib source id
pending_paired_reconnect = {}           # MAC -> GLib source id
paired_reconnect_attempts = {}          # MAC -> số lần đã gọi Device1.Connect
pending_disconnect_confirm = {}         # MAC -> GLib source id, xác nhận Connected=False không phải trạng thái thoáng qua
pending_connection_activation = {}      # MAC -> GLib source id, chống nhận nhầm Connected=True cũ trước pairing
post_pair_reconnect_active = set()      # MAC đang nằm trong cửa sổ bảo vệ reconnect sau pairing
pending_post_pair_stable = {}           # MAC -> GLib source id, xác nhận A2DP đã ổn định
pending_post_pair_expiry = {}           # MAC -> GLib source id, tự dọn cửa sổ reconnect sau 20s
pre_pair_connected = set()              # ACL/link tạm đã Connected=True nhưng chưa Paired, không coi là kết nối hoàn chỉnh
connected_devices = OrderedDict()       #tập hợp các MAC đang kết nối
visibility_timer = None                 # lưu ID của timer đang chờ thay đổi trạng thái hiển thị Bluetooth

active_playback_device = None           # MAC của thiết bị đang phát âm thanh
active_playback_process = None          # process ID của bluealsa-aplay đang chạy
suspended_playback_device = None        # MAC cần khôi phục sau khi AirPlay kết thúc
playback_generation = 0                 # vô hiệu hóa callback delayed-start cũ
pending_audio_start = {}                # MAC -> GLib source id, tránh health-check hiểu nhầm là process đã chết
last_audio_start_at = {}                # MAC -> thời điểm spawn bluealsa-aplay gần nhất
external_playback_pid = None            # PID bluealsa-aplay hệ thống (ví dụ: /usr/bin/bluealsa-aplay -S)
external_playback_cmdline = None
external_playback_announced_for = None  # tránh lặp log khi player hệ thống đã được phát hiện
softvolume_enabled = set()              # MAC đã bật SoftVolume cho PCM hiện tại; reset khi PCM biến mất
default_volume_applied = set()          # MAC đã được đặt mức dự phòng trong phiên kết nối hiện tại
playback_lock = threading.RLock()
device_names_cache = {}                 # cache tên thiết bị để tránh gọi bluetoothctl info nhiều lần
main_loop = None
agent_manager = None
agent_registered = False
shutdown_requested = False
scheduled_sources = set()

def log(msg):
    ts = datetime.now().strftime("%d/%m/%Y - %H:%M:%S")
    print(f"[{ts}] [VBot-Bluetooth] {msg}", flush=True)

def run(cmd, timeout=COMMAND_TIMEOUT):
    try:
        if isinstance(cmd, str):
            cmd = shlex.split(cmd)
        proc = subprocess.run(cmd, shell=False, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, check=False, timeout=timeout)
        return proc.stdout.decode(errors="ignore")
    except subprocess.TimeoutExpired:
        log(f"Lệnh quá thời gian {timeout}s: {' '.join(cmd)}")
        return ""
    except Exception as e:
        log(f"Lỗi run(): {e}")
        return ""

#Theo dõi bộ hẹn giờ GLib để quá trình có thể hủy bỏ mọi lệnh gọi lại đang chờ xử lý.
def schedule_timeout_seconds(delay, callback, *args):
    holder = {}
    def wrapped():
        repeat = False
        if shutdown_requested:
            scheduled_sources.discard(holder.get("id"))
            return False
        try:
            repeat = bool(callback(*args))
            return repeat
        finally:
            if not repeat:
                scheduled_sources.discard(holder.get("id"))
    source_id = GLib.timeout_add_seconds(delay, wrapped)
    holder["id"] = source_id
    scheduled_sources.add(source_id)
    return source_id

def cancel_scheduled_source(source_id):
    if not source_id:
        return
    scheduled_sources.discard(source_id)
    try:
        GLib.source_remove(source_id)
    except Exception:
        pass

def device_path_to_mac(path):
    adapter_prefix = f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_"
    if not path or not str(path).startswith(adapter_prefix): return None
    return path.split("dev_", 1)[1].replace("_", ":").upper()

_system_bus = None
def get_system_bus():
    global _system_bus
    if _system_bus is None: _system_bus = dbus.SystemBus()
    return _system_bus

def adapter_path():
    return f"/org/bluez/{BLUETOOTH_ADAPTER}"

def get_adapter_properties():
    obj = get_system_bus().get_object(BUS_NAME, adapter_path())
    return dbus.Interface(obj, "org.freedesktop.DBus.Properties")

#Xác minh adapter cấu hình tồn tại, nếu không thì chuyển an toàn về hci0
def select_available_adapter(bus):
    global BLUETOOTH_ADAPTER
    configured = BLUETOOTH_ADAPTER
    for candidate in (configured, DEFAULT_BLUETOOTH_ADAPTER):
        try:
            obj = bus.get_object(BUS_NAME, f"/org/bluez/{candidate}")
            props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
            props.GetAll("org.bluez.Adapter1")
            BLUETOOTH_ADAPTER = candidate
            if candidate != configured:
                log(f"Adapter '{configured}' không tồn tại, tự động dùng {candidate}")
            return candidate
        except Exception:
            continue
    BLUETOOTH_ADAPTER = DEFAULT_BLUETOOTH_ADAPTER
    raise RuntimeError("Không tìm thấy Bluetooth adapter hci0 hoặc adapter đã cấu hình")

#Chỉ để adapter đã cấu hình hoạt động, tránh phát hiện/ghép đôi nhầm controller
def disable_unselected_adapters(bus):
    manager = dbus.Interface(bus.get_object(BUS_NAME, "/"), "org.freedesktop.DBus.ObjectManager")
    for path, interfaces in manager.GetManagedObjects().items():
        if "org.bluez.Adapter1" not in interfaces or str(path) == adapter_path():
            continue
        adapter_name = str(path).rsplit("/", 1)[-1]
        try:
            props = dbus.Interface(bus.get_object(BUS_NAME, path), "org.freedesktop.DBus.Properties")
            #Ẩn adapter trước rồi mới tắt nguồn để không nhận thêm yêu cầu pairing trong lúc chuyển trạng thái.
            props.Set("org.bluez.Adapter1", "Discoverable", dbus.Boolean(False))
            props.Set("org.bluez.Adapter1", "Pairable", dbus.Boolean(False))
            props.Set("org.bluez.Adapter1", "Powered", dbus.Boolean(False))
            log(f"Đã tắt Bluetooth adapter không được chọn: {adapter_name}")
        except Exception as error:
            log(f"Không thể tắt Bluetooth adapter {adapter_name}: {error}")

def is_actually_connected(mac):
    try:
        bus = get_system_bus()
        obj = bus.get_object(BUS_NAME, f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_{mac.upper().replace(':', '_')}")
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        return bool(props.Get("org.bluez.Device1", "Connected"))
    except Exception:
        return False

def get_kernel_connected_macs():
    """Lấy ACL/baseband đang kết nối trực tiếp từ kernel khi có thể.

    Không dùng PCM A2DP để suy ra trạng thái Bluetooth vì thiết bị có thể vẫn
    Connected nhưng đang idle/chưa phát nhạc. Trả về None nếu hệ thống không có
    công cụ phù hợp; caller sẽ fallback về Device1.Connected.
    """
    mac_re = re.compile(r"(?:[0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}")
    commands = [["hcitool", "-i", BLUETOOTH_ADAPTER, "con"]]
    match = re.fullmatch(r"hci(\d+)", BLUETOOTH_ADAPTER)
    if match:
        commands.append(["btmgmt", "--index", match.group(1), "con"])
    for command in commands:
        try:
            result = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, text=True, check=False, timeout=3)
        except (FileNotFoundError, subprocess.TimeoutExpired, OSError):
            continue
        if result.returncode != 0:
            continue
        return {m.upper() for m in mac_re.findall(result.stdout or "")}
    return None

def has_live_acl_connection(mac):
    """Xác nhận ACL thật; None nghĩa là không thể kiểm tra ở mức kernel."""
    if not mac:
        return False
    connected = get_kernel_connected_macs()
    if connected is None:
        return None
    return mac.upper() in connected

def connection_is_live(mac):
    """Ưu tiên trạng thái kernel khi D-Bus vừa báo False/transient."""
    if is_actually_connected(mac):
        return True
    kernel_state = has_live_acl_connection(mac)
    if kernel_state is None:
        return False
    return bool(kernel_state)

def get_device_name(mac):
    if not mac: return ""
    mac = mac.upper()
    if mac in device_names_cache and device_names_cache[mac]: return device_names_cache[mac]
    try:
        bus = get_system_bus()
        om = bus.get_object(BUS_NAME, "/")
        iface = dbus.Interface(om, "org.freedesktop.DBus.ObjectManager")
        objects = iface.GetManagedObjects()
        for path, interfaces in objects.items():
            if not str(path).startswith(f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_"):
                continue
            if "org.bluez.Device1" in interfaces:
                dev_mac = device_path_to_mac(path)
                if dev_mac == mac:
                    props = interfaces["org.bluez.Device1"]
                    name = str(props.get("Alias", props.get("Name", "")))
                    if name:
                        device_names_cache[mac] = name
                        return name
    except Exception:
        pass
    return mac

def device_info_str(mac):
    name = get_device_name(mac)
    return f"{mac} ({name})" if name != mac else mac

#Đọc trạng thái SoftVolume thực tế của PCM hiện tại.
#Trả về None khi PCM chưa tồn tại/không đọc được, False khi property đang tắt.
def bluealsa_pcm_path(mac):
    if not mac:
        return None
    return f"/org/bluealsa/{BLUETOOTH_ADAPTER}/dev_{mac.replace(':', '_')}/a2dpsnk/source"

def get_softvolume_state(mac, pcm_path=None):
    if not mac:
        return None
    path = pcm_path or bluealsa_pcm_path(mac)
    try:
        obj = get_system_bus().get_object("org.bluealsa", path)
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        return bool(props.Get("org.bluealsa.PCM1", "SoftVolume"))
    except Exception:
        return None

def apply_default_volume_once(mac, pcm_path=None, props=None):
    """Áp dụng volume mặc định đúng một lần cho mỗi phiên kết nối.

    DEFAULT_SOFTVOLUME_PERCENT:
      - None: Auto, không ép mức volume.
      - 0..100: đặt mức SoftVolume mặc định tương ứng.

    SoftVolume phải được bật trước khi ghi Volume để tránh tác động
    tới AVRCP/native volume của thiết bị phát.
    """
    if not mac or mac in default_volume_applied:
        return True
    if (mac not in connected_devices or not is_actually_connected(mac) or not is_paired(mac)):
        return False
    path = pcm_path or bluealsa_pcm_path(mac)
    try:
        if props is None:
            obj = get_system_bus().get_object("org.bluealsa", path)
            props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")

        # SoftVolume phải đang bật trước khi xử lý volume.
        if not bool(props.Get("org.bluealsa.PCM1", "SoftVolume")):
            return False

        # AUTO:
        # Giữ nguyên volume hiện tại, không ép về một mức cố định.
        if DEFAULT_SOFTVOLUME_PERCENT is None:
            default_volume_applied.add(mac)
            return True
        current = props.Get("org.bluealsa.PCM1", "Volume")
        channel_count = len(current)
        if channel_count <= 0:
            return False
        level = max(0, min(A2DP_VOLUME_MAX, round(A2DP_VOLUME_MAX * DEFAULT_SOFTVOLUME_PERCENT / 100.0)))
        volume = dbus.Array([dbus.Byte(level) for _ in range(channel_count)], signature="y")
        props.Set("org.bluealsa.PCM1", "Volume", volume)
        verify = props.Get("org.bluealsa.PCM1", "Volume")
        if len(verify) != channel_count:
            return False
        if not all((int(value) & 0x7F) == level and (int(value) & 0x80) == 0 for value in verify):
            return False
        default_volume_applied.add(mac)
        log(f"Đã đặt âm lượng SoftVolume dự phòng {DEFAULT_SOFTVOLUME_PERCENT}% ({level}/{A2DP_VOLUME_MAX}) cho: {device_info_str(mac)}")
        return True
    except Exception:
        # PCM có thể chưa tồn tại hoặc vừa biến mất.
        # Health-check / InterfacesAdded sẽ xử lý lại.
        return False

#Bật SoftVolume trực tiếp cho thiết bị BlueALSA
def set_softvolume_true(mac, attempt=1, pcm_path=None):
    if not mac:
        return False
    if mac not in connected_devices or not is_actually_connected(mac) or not is_paired(mac):
        softvolume_enabled.discard(mac)
        return False

    # Nếu callback không cung cấp path thì xác nhận PCM đã xuất hiện trước.
    # Không có PCM không phải lỗi: điện thoại có thể vẫn kết nối nhưng chưa phát nhạc.
    if pcm_path is None and not has_bluealsa_audio_pcm(mac):
        softvolume_enabled.discard(mac)
        return False

    path = pcm_path or bluealsa_pcm_path(mac)
    try:
        obj = get_system_bus().get_object("org.bluealsa", path)
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        props.Set("org.bluealsa.PCM1", "SoftVolume", dbus.Boolean(True))
        enabled = bool(props.Get("org.bluealsa.PCM1", "SoftVolume"))
        if enabled:
            first_enable = mac not in softvolume_enabled
            softvolume_enabled.add(mac)
            if first_enable:
                log(f"Xác nhận kiểm tra: SoftVolume đã bật thành công cho {mac}")

            # Chỉ đặt 50% một lần cho phiên kết nối. Nếu người dùng thay đổi
            # volume sau đó, health-check chỉ bảo vệ SoftVolume=True chứ không
            # ép volume quay lại 50%.
            apply_default_volume_once(mac, pcm_path=path, props=props)
            return False
    except Exception as e:
        # Chỉ log khi PCM/link vẫn thực sự tồn tại; tránh cảnh báo giả lúc idle.
        if pcm_path is not None and is_actually_connected(mac):
            log(f"Chưa thể bật SoftVolume ngay cho {mac}: {e}")

    # Nếu link/PCM biến mất trong lúc thao tác thì đó là idle/disconnect, không
    # phải lỗi SoftVolume và không cần cảnh báo giả.
    if (mac not in connected_devices or not is_actually_connected(mac) or not is_paired(mac) or (pcm_path is None and not has_bluealsa_audio_pcm(mac))):
        softvolume_enabled.discard(mac)
        return False

    if attempt < SOFTVOLUME_MAX_ATTEMPTS:
        log(
            f"Chưa thể bật SoftVolume cho {mac}, "
            f"thử lại {attempt + 1}/{SOFTVOLUME_MAX_ATTEMPTS} sau {SOFTVOLUME_RETRY_DELAY}s"
        )
        schedule_timeout_seconds(SOFTVOLUME_RETRY_DELAY, set_softvolume_true, mac, attempt + 1)
    else:
        log(f"Cảnh báo: SoftVolume cho {mac} vẫn chưa bật sau {attempt} lần thử")
    return False

def bluealsa_path_to_mac(path):
    """Lấy MAC từ object path BlueALSA."""
    prefix = f"/org/bluealsa/{BLUETOOTH_ADAPTER}/dev_"
    value = str(path or "")
    if not value.startswith(prefix):
        return None
    encoded = value[len(prefix):].split("/", 1)[0]
    if not re.fullmatch(r"(?:[0-9A-Fa-f]{2}_){5}[0-9A-Fa-f]{2}", encoded):
        return None
    return encoded.replace("_", ":").upper()

def bluealsa_interfaces_added(path, interfaces):
    """Cấu hình SoftVolume ngay khi PCM A2DP được BlueALSA tạo.

    Callback này giảm tối đa cửa sổ native-volume trước khi health-check định kỳ
    chạy, nhờ đó hạn chế điện thoại bị đồng bộ volume lên 100%.
    """
    if "org.bluealsa.PCM1" not in interfaces:
        return
    path_str = str(path)
    if "/a2dpsnk/source" not in path_str:
        return
    mac = bluealsa_path_to_mac(path_str)
    if not mac or mac not in connected_devices or not is_paired(mac) or not is_actually_connected(mac):
        return
    set_softvolume_true(mac, 1, path_str)


def bluealsa_interfaces_removed(path, interfaces):
    if "org.bluealsa.PCM1" not in interfaces:
        return
    mac = bluealsa_path_to_mac(path)
    if mac:
        # Chỉ reset trạng thái PCM. Không xóa default_volume_applied ở đây vì
        # cùng một kết nối có thể idle rồi tạo PCM lại; không được ép người dùng
        # về 50% sau mỗi lần pause/resume.
        softvolume_enabled.discard(mac)

def is_paired(mac):
    try:
        bus = get_system_bus()
        obj = bus.get_object(BUS_NAME, f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_{mac.upper().replace(':', '_')}")
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        return bool(props.Get("org.bluez.Device1", "Paired"))
    except Exception:
        pass
    return False

def trust(mac):
    if not mac:
        return
    try:
        path = f"{adapter_path()}/dev_{mac.upper().replace(':', '_')}"
        obj = get_system_bus().get_object(BUS_NAME, path)
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        # Tránh set/log Trusted=True lặp lại ở mỗi lần reconnect/profile authorize.
        try:
            if bool(props.Get("org.bluez.Device1", "Trusted")):
                return
        except Exception:
            pass
        log(f"Đánh dấu trust tin cậy thiết bị: {device_info_str(mac)}")
        props.Set("org.bluez.Device1", "Trusted", dbus.Boolean(True))
    except Exception as error:
        log(f"Không thể trust {mac} trên {BLUETOOTH_ADAPTER}: {error}")

def is_running(proc): return proc is not None and proc.poll() is None

#Chỉ được phép dừng process do agent này tạo.
def cancel_pending_audio_start(mac=None):
    if mac is not None:
        source_id = pending_audio_start.pop(mac, None)
        if source_id:
            cancel_scheduled_source(source_id)
        return
    for device_mac, source_id in list(pending_audio_start.items()):
        pending_audio_start.pop(device_mac, None)
        cancel_scheduled_source(source_id)

def find_other_bluealsa_aplay(mac=None):
    """Tìm bluealsa-aplay ngoài process do agent tạo.

    Nếu truyền MAC, chỉ dùng process chạy toàn cục (không khóa vào MAC nào) hoặc
    process đang phục vụ đúng MAC đó. Tránh nhận nhầm player riêng của thiết bị khác.
    """
    tracked_pid = None
    target_mac = mac.upper() if mac else None
    mac_pattern = re.compile(r"(?:[0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}")
    if active_playback_process is not None and is_running(active_playback_process):
        tracked_pid = active_playback_process.pid
    try:
        for entry in os.listdir('/proc'):
            if not entry.isdigit():
                continue
            pid = int(entry)
            if pid in (os.getpid(), tracked_pid):
                continue
            try:
                raw = Path(f'/proc/{pid}/cmdline').read_bytes()
            except Exception:
                continue
            if not raw:
                continue
            args = [part.decode(errors='ignore') for part in raw.split(b'\0') if part]
            if not any(os.path.basename(arg) == 'bluealsa-aplay' for arg in args):
                continue
            cmdline = ' '.join(args)
            bound_macs = {m.upper() for m in mac_pattern.findall(cmdline)}
            if target_mac and bound_macs and target_mac not in bound_macs:
                continue
            return pid, cmdline
    except Exception:
        pass
    return None

def is_pid_alive(pid):
    if not pid:
        return False
    try:
        os.kill(int(pid), 0)
        return True
    except (OSError, ValueError, TypeError):
        return False

def get_bluealsa_pcm_listing():
    """Liệt kê PCM BlueALSA hiện có mà không mở/chiếm PCM."""
    try:
        result = subprocess.run(["bluealsa-aplay", "-L"], stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, text=True, check=False, timeout=3)
        return result.stdout or ""
    except Exception:
        return ""

def has_bluealsa_audio_pcm(mac, listing=None):
    """Xác nhận BlueALSA thực sự đã tạo PCM cho MAC, tránh tin Connected=True stale của BlueZ."""
    if not mac:
        return False
    if listing is None:
        listing = get_bluealsa_pcm_listing()
    if not listing:
        return False
    upper = listing.upper()
    mac_colon = mac.upper()
    mac_under = mac_colon.replace(':', '_')
    return mac_colon in upper or mac_under in upper

def use_external_bluealsa_player(mac, player=None):
    """Dùng bluealsa-aplay hệ thống nếu đã có, không spawn process thứ hai tranh PCM."""
    global external_playback_pid, external_playback_cmdline, external_playback_announced_for
    global active_playback_process, active_playback_device, playback_generation
    if player is None:
        player = find_other_bluealsa_aplay(mac)
    if not player:
        external_playback_pid = None
        external_playback_cmdline = None
        external_playback_announced_for = None
        return False

    pid, cmdline = player
    cancel_pending_audio_start()
    with playback_lock:
        playback_generation += 1
        active_playback_process = None
        active_playback_device = mac
    external_playback_pid = pid
    external_playback_cmdline = cmdline

    announce_key = (pid, mac)
    if external_playback_announced_for != announce_key:
        log(f"Dùng bluealsa-aplay hệ thống đang chạy (PID {pid}) để nhận âm thanh: {device_info_str(mac)}")
        external_playback_announced_for = announce_key
    return True

def stop_bluealsa_playback():
    global active_playback_process, active_playback_device, playback_generation
    global external_playback_announced_for
    cancel_pending_audio_start()
    with playback_lock:
        playback_generation += 1
        process = active_playback_process
        device = active_playback_device
        active_playback_process = None
        active_playback_device = None
    external_playback_announced_for = None
    # Chỉ dừng process do chính agent tạo. bluealsa-aplay hệ thống phải được giữ nguyên.
    if process and process.poll() is None:
        try:
            process.terminate()
            process.wait(timeout=2)
            log(f"Dừng phát âm thanh từ thiết bị: {device_info_str(device)}")
        except subprocess.TimeoutExpired:
            process.kill()
            process.wait(timeout=2)
        except Exception as error:
            log(f"Lỗi khi dừng bluealsa-aplay: {error}")

def start_bluealsa_playback(mac, delay=AUDIO_START_DELAY):
    global active_playback_process, active_playback_device, playback_generation
    if not mac:
        return
    if os.path.exists(BT_AUDIO_SUSPEND_FILE):
        log("BlueALSA đang tạm nhường đầu ra cho AirPlay")
        return
    if mac in pairing_devices:
        log(f"Đang pairing {device_info_str(mac)}, tạm hoãn mở BlueALSA PCM")
        return
    if not is_paired(mac):
        log(f"Chờ thiết bị {mac} ghép đôi xong mới mở âm thanh...")
        return
    # Connected=True chỉ được dùng cho audio sau khi đã được promote thành
    # connected_devices. Điều này chặn ACL/link tạm trước pairing.
    if mac not in connected_devices or not is_actually_connected(mac):
        return
    # VBot đã có bluealsa-aplay hệ thống chạy thường trực (ví dụ -S). Process
    # này vẫn là audio receiver hợp lệ kể cả lúc điện thoại chưa phát nhạc/không
    # có PCM A2DP, nên nhận diện nó trước khi kiểm tra PCM.
    external = find_other_bluealsa_aplay(mac)
    if external:
        use_external_bluealsa_player(mac, external)
        return

    # Nếu agent phải tự tạo player riêng thì chỉ mở khi PCM A2DP đã xuất hiện.
    # Không có PCM trong lúc idle không đồng nghĩa Bluetooth đã ngắt kết nối.
    if not has_bluealsa_audio_pcm(mac):
        return

    with playback_lock:
        if active_playback_device == mac and is_running(active_playback_process):
            return
        if active_playback_device == mac and mac in pending_audio_start:
            return

    stop_bluealsa_playback()
    with playback_lock:
        active_playback_device = mac
        playback_generation += 1
        generation = playback_generation

    try:
        source_id = schedule_timeout_seconds(max(1, int(delay)), _delayed_start_audio, mac, generation)
        pending_audio_start[mac] = source_id
        log(f"Đã lên lịch nhận âm thanh sau {max(1, int(delay))}s để chờ A2DP ổn định: {device_info_str(mac)}")
    except Exception as error:
        log(f"Không thể lên lịch phát BlueALSA cho {mac}: {error}")

def _delayed_start_audio(mac, generation):
    global active_playback_process
    pending_audio_start.pop(mac, None)
    with playback_lock:
        if (shutdown_requested or generation != playback_generation or active_playback_device != mac):
            return False
    if mac in pairing_devices:
        log(f"Pairing vẫn đang diễn ra, chưa mở BlueALSA PCM cho: {device_info_str(mac)}")
        return False
    if not is_actually_connected(mac) or not is_paired(mac):
        return False

    other = find_other_bluealsa_aplay(mac)
    if other:
        use_external_bluealsa_player(mac, other)
        return False

    # Connected=True chưa đủ để chứng minh A2DP thực sự sẵn sàng. Chỉ mở PCM
    # khi BlueALSA đã công bố PCM tương ứng cho thiết bị.
    if not has_bluealsa_audio_pcm(mac):
        return False

    try:
        log(f"Cho phép nhận âm thanh từ thiết bị: {device_info_str(mac)}")

        # Nếu agent phải tự tạo player, ép software-volume ngay từ lúc mở PCM
        # để không có cửa sổ native/AVRCP trước khi SoftVolume được set.
        process = subprocess.Popen(["bluealsa-aplay", "--volume=software", mac], stdout=subprocess.DEVNULL, stderr=None)
        last_audio_start_at[mac] = time.time()
        with playback_lock:
            stale = generation != playback_generation or active_playback_device != mac
            if not stale:
                active_playback_process = process
        if stale:
            process.terminate()
            try:
                process.wait(timeout=2)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=2)
            return False
    except Exception as error:
        log(f"Không thể khởi động bluealsa-aplay cho {mac}: {error}")
        with playback_lock:
            if generation == playback_generation:
                active_playback_process = None
    return False

def switch_to_next_device():
    if not connected_devices: stop_bluealsa_playback(); return
    for m in list(connected_devices.keys()):
        if is_actually_connected(m) and is_paired(m):
            log(f"Chuyển tín hiệu sang thiết bị: {device_info_str(m)}")
            start_bluealsa_playback(m)
            return
    stop_bluealsa_playback()

def ensure_primary_audio():
    if not connected_devices: stop_bluealsa_playback(); return
    primary = next(iter(connected_devices))
    if active_playback_device != primary and is_paired(primary):
        # start_bluealsa_playback() tự quyết định dùng player hệ thống, chờ PCM
        # hay tạo player riêng; không log lặp mỗi health-check khi điện thoại idle.
        start_bluealsa_playback(primary)

#Chỉ khởi động lại tiến trình phát lại đã chọn nếu bị thoát đột ngột
def playback_health_check():
    global suspended_playback_device, external_playback_pid, external_playback_cmdline
    if shutdown_requested:
        return False
    if os.path.exists(BT_AUDIO_SUSPEND_FILE):
        if active_playback_device:
            suspended_playback_device = active_playback_device
        if is_running(active_playback_process) or pending_audio_start:
            stop_bluealsa_playback()
        return True

    device = active_playback_device or suspended_playback_device

    # Không dùng PCM để suy ra trạng thái kết nối. Nếu D-Bus vừa báo False thì
    # kiểm tra thêm ACL kernel; chỉ loại state khi cả hai đều xác nhận đã rớt.
    if device and (not is_paired(device) or not is_actually_connected(device)):
        kernel_live = has_live_acl_connection(device) if is_paired(device) else False
        if not is_paired(device) or kernel_live is not True:
            connected_devices.pop(device, None)
            last_connected_at.pop(device, None)
            softvolume_enabled.discard(device)
            suspended_playback_device = None
            if active_playback_device == device:
                stop_bluealsa_playback()
            if not connected_devices:
                set_visibility(True)
            if should_post_pair_reconnect(device):
                schedule_post_pair_disconnect_confirm(device)
            device = None

    if device and active_playback_device is None:
        suspended_playback_device = None
        log(f"Khôi phục BlueALSA sau khi AirPlay kết thúc: {device_info_str(device)}")
        start_bluealsa_playback(device)
        return True

    if not device and connected_devices:
        ensure_primary_audio()
        return True

    if not device:
        return True

    pcm_available = has_bluealsa_audio_pcm(device)
    if not pcm_available:
        # Điện thoại có thể Connected nhưng idle. Giữ connected_devices/player.
        # Khi PCM xuất hiện trở lại, vòng health-check kế tiếp sẽ bật SoftVolume.
        softvolume_enabled.discard(device)
    else:
        # Không chỉ tin cache nội bộ: kiểm tra property thật định kỳ. Nếu BlueALSA
        # tạo lại PCM hoặc SoftVolume bị reset về false thì bật lại ngay.
        softvolume_state = get_softvolume_state(device)
        if softvolume_state is True:
            softvolume_enabled.add(device)
            if device not in default_volume_applied:
                apply_default_volume_once(device)
        else:
            softvolume_enabled.discard(device)
            set_softvolume_true(device, 1)

    # Player hệ thống có thể chạy thường trực kể cả khi chưa có PCM.
    if external_playback_pid and is_pid_alive(external_playback_pid):
        return True

    if external_playback_pid and not is_pid_alive(external_playback_pid):
        external_playback_pid = None
        external_playback_cmdline = None

    external = find_other_bluealsa_aplay(device)
    if external:
        use_external_bluealsa_player(device, external)
        return True

    if device in pending_audio_start or device in pairing_devices:
        return True

    if (is_actually_connected(device) and is_paired(device) and pcm_available and not is_running(active_playback_process)):
        last_started = last_audio_start_at.get(device, 0)
        if (time.time() - last_started) < AUDIO_RESTART_COOLDOWN:
            return True
        exit_code = active_playback_process.poll() if active_playback_process is not None else "chưa chạy"
        log(
            f"bluealsa-aplay do agent quản lý không còn hoạt động (exit={exit_code}), "
            f"lên lịch khởi động lại cho: {device_info_str(device)}"
        )
        start_bluealsa_playback(device)
    return True

def set_visibility(visible):
    try:
        props = get_adapter_properties()
        props.Set("org.bluez.Adapter1", "Pairable", dbus.Boolean(bool(visible)))
        props.Set("org.bluez.Adapter1", "Discoverable", dbus.Boolean(bool(visible)))
    except Exception as error:
        log(f"Không thể đổi trạng thái hiển thị của {BLUETOOTH_ADAPTER}: {error}")
        return
    if visible:
        log("Trạng thái Bluetooth: Đã hiển thị tên thiết bị (Cho phép các thiết bị khác tìm thấy Bluetooth)")
    else:
        log("Trạng thái Bluetooth: Đã ẩn tên thiết bị (Để duy trì 1 kết nối với thiết bị hiện tại)")

def clear_device_state(mac):
    if not mac: return
    cancel_pending_audio_start(mac)
    last_audio_start_at.pop(mac, None)
    cancel_pending_device_task(pending_unpaired_cleanup, mac)
    cancel_pending_device_task(pending_paired_reconnect, mac)
    cancel_pending_device_task(pending_disconnect_confirm, mac)
    cancel_pending_device_task(pending_connection_activation, mac)
    cancel_pending_device_task(pending_post_pair_stable, mac)
    cancel_pending_device_task(pending_post_pair_expiry, mac)
    pairing_devices.discard(mac)
    pairing_started_at.pop(mac, None)
    paired_reconnect_attempts.pop(mac, None)
    paired_completed_at.pop(mac, None)
    post_pair_reconnect_active.discard(mac)
    pre_pair_connected.discard(mac)
    softvolume_enabled.discard(mac)
    default_volume_applied.discard(mac)
    connected_devices.pop(mac, None)
    last_connected_at.pop(mac, None)
    device_names_cache.pop(mac, None)

def cancel_pending_device_task(tasks, mac):
    source_id = tasks.pop(mac, None)
    if source_id:
        cancel_scheduled_source(source_id)

def arm_post_pair_reconnect(mac):
    """Bật bảo vệ reconnect cho đúng một phiên pairing mới."""
    if not mac:
        return
    if mac in post_pair_reconnect_active:
        return
    paired_completed_at[mac] = time.time()
    post_pair_reconnect_active.add(mac)
    cancel_pending_device_task(pending_post_pair_expiry, mac)
    pending_post_pair_expiry[mac] = schedule_timeout_seconds(POST_PAIR_RECONNECT_WINDOW, _expire_post_pair_reconnect, mac)

def _expire_post_pair_reconnect(mac):
    pending_post_pair_expiry.pop(mac, None)
    clear_post_pair_reconnect(mac)
    return False

def clear_post_pair_reconnect(mac, reason=None):
    """Tắt bảo vệ reconnect khi kết nối đã ổn định hoặc cửa sổ đã hết."""
    was_active = mac in post_pair_reconnect_active
    post_pair_reconnect_active.discard(mac)
    paired_completed_at.pop(mac, None)
    paired_reconnect_attempts.pop(mac, None)
    cancel_pending_device_task(pending_paired_reconnect, mac)
    cancel_pending_device_task(pending_disconnect_confirm, mac)
    cancel_pending_device_task(pending_post_pair_stable, mac)
    cancel_pending_device_task(pending_post_pair_expiry, mac)
    if was_active and reason:
        log(f"Kết nối sau pairing đã ổn định, tắt tự reconnect: {device_info_str(mac)} ({reason})")

def should_post_pair_reconnect(mac, now=None):
    if not mac or mac not in post_pair_reconnect_active or not is_paired(mac):
        return False
    completed = paired_completed_at.get(mac)
    if not completed:
        clear_post_pair_reconnect(mac)
        return False
    age = (now or time.time()) - completed
    if age <= 0 or age > POST_PAIR_RECONNECT_WINDOW:
        clear_post_pair_reconnect(mac)
        return False
    return True

def mark_post_pair_connection_stable(mac, reason="A2DP đã ổn định"):
    if mac not in post_pair_reconnect_active:
        return False
    if (mac not in connected_devices
            or not is_actually_connected(mac)
            or not is_paired(mac)):
        return False
    clear_post_pair_reconnect(mac, reason)
    # Khi kết nối đã ổn định, SoftVolume là trạng thái bắt buộc để điều khiển
    # âm lượng. Nếu PCM chưa xuất hiện vì điện thoại đang idle thì health-check
    # sẽ bật ngay khi PCM được tạo.
    set_softvolume_true(mac, 1)
    return False

def schedule_post_pair_stable_check(mac):
    if not should_post_pair_reconnect(mac):
        return
    cancel_pending_device_task(pending_post_pair_stable, mac)
    pending_post_pair_stable[mac] = schedule_timeout_seconds(POST_PAIR_STABLE_DELAY, _post_pair_stable_check, mac)


def _post_pair_stable_check(mac):
    pending_post_pair_stable.pop(mac, None)
    if not should_post_pair_reconnect(mac):
        return False
    mark_post_pair_connection_stable(mac, f"Bluetooth duy trì ổn định {POST_PAIR_STABLE_DELAY}s")
    return False

def schedule_connection_activation(mac, path=None):
    """Trì hoãn công nhận Connected=True của thiết bị paired cũ.

    Nếu trong thời gian này BlueZ bắt đầu pairing lại do khóa cũ không còn hợp lệ,
    remember_pairing_device() sẽ hủy timer nên không nhận nhầm thành kết nối hoàn chỉnh.
    """
    if not mac:
        return
    cancel_pending_device_task(pending_connection_activation, mac)
    pending_connection_activation[mac] = schedule_timeout_seconds(CONNECTED_ACTIVATION_DELAY, _delayed_connection_activation, mac, path)

def _delayed_connection_activation(mac, path=None):
    pending_connection_activation.pop(mac, None)
    if mac in pairing_devices:
        return False
    if not is_actually_connected(mac) or not is_paired(mac):
        return False
    activate_paired_connection(mac, path=path)
    return False

def remove_device(mac):
    if not mac: return
    log(f"Tiến hành dọn dẹp thiết bị kết nối lỗi: {device_info_str(mac)}")
    try:
        adapter_obj = get_system_bus().get_object(BUS_NAME, adapter_path())
        adapter_iface = dbus.Interface(adapter_obj, "org.bluez.Adapter1")
        device_path = f"{adapter_path()}/dev_{mac.upper().replace(':', '_')}"
        adapter_iface.RemoveDevice(dbus.ObjectPath(device_path))
    except Exception as error:
        log(f"Không thể xóa {mac} khỏi {BLUETOOTH_ADAPTER}: {error}")

def cleanup_unpaired_device(mac):
    pending_unpaired_cleanup.pop(mac, None)
    if mac in pairing_devices or is_paired(mac) or is_actually_connected(mac):
        return False
    log(f"Dọn thiết bị tạm chưa ghép đôi để có thể thử lại ngay: {device_info_str(mac)}")
    remove_device(mac)
    return False

def schedule_unpaired_cleanup(mac):
    if not mac or mac in pairing_devices or is_paired(mac):
        return
    cancel_pending_device_task(pending_unpaired_cleanup, mac)
    pending_unpaired_cleanup[mac] = schedule_timeout_seconds(UNPAIRED_CLEANUP_DELAY, cleanup_unpaired_device, mac)

def schedule_post_pair_disconnect_confirm(mac):
    """Xác nhận Connected=False vẫn còn sau một khoảng ngắn trước khi reconnect."""
    if not should_post_pair_reconnect(mac):
        return
    cancel_pending_device_task(pending_disconnect_confirm, mac)
    pending_disconnect_confirm[mac] = schedule_timeout_seconds(DISCONNECT_CONFIRM_DELAY, _confirm_post_pair_disconnect, mac)

def _confirm_post_pair_disconnect(mac):
    pending_disconnect_confirm.pop(mac, None)
    if not should_post_pair_reconnect(mac):
        return False
    if connection_is_live(mac):
        if is_actually_connected(mac) and mac not in pairing_devices:
            activate_paired_connection(mac)
        return False
    paired_age = time.time() - paired_completed_at.get(mac, time.time())
    log(f"Xác nhận thiết bị đã rớt sau pairing ({paired_age:.1f}s), sẽ chủ động nối lại: {device_info_str(mac)}")
    schedule_paired_reconnect(mac)
    return False

def reconnect_paired_device(mac):
    pending_paired_reconnect.pop(mac, None)
    if not should_post_pair_reconnect(mac):
        paired_reconnect_attempts.pop(mac, None)
        return False
    if connection_is_live(mac):
        paired_reconnect_attempts.pop(mac, None)
        if is_actually_connected(mac) and mac not in pairing_devices:
            activate_paired_connection(mac)
        return False
    attempt = paired_reconnect_attempts.get(mac, 0) + 1
    paired_reconnect_attempts[mac] = attempt
    log(f"Kết nối lại profile Bluetooth sau pairing, lần {attempt}/{PAIRED_RECONNECT_MAX_ATTEMPTS}: {device_info_str(mac)}")
    try:
        path = f"{adapter_path()}/dev_{mac.upper().replace(':', '_')}"
        obj = get_system_bus().get_object(BUS_NAME, path)
        dbus.Interface(obj, "org.bluez.Device1").Connect()
    except Exception as error:
        log(f"Chưa thể kết nối lại {device_info_str(mac)}: {error}")
    if connection_is_live(mac):
        paired_reconnect_attempts.pop(mac, None)
        if is_actually_connected(mac) and mac not in pairing_devices:
            activate_paired_connection(mac)
    elif attempt < PAIRED_RECONNECT_MAX_ATTEMPTS:
        pending_paired_reconnect[mac] = schedule_timeout_seconds(PAIRED_RECONNECT_DELAY, reconnect_paired_device, mac)
    else:
        paired_reconnect_attempts.pop(mac, None)
    return False

def schedule_paired_reconnect(mac):
    # Chỉ reconnect trong cửa sổ bảo vệ của phiên pairing mới. Khi A2DP đã ổn định
    # hoặc quá POST_PAIR_RECONNECT_WINDOW, mọi timer reconnect sẽ tự dừng.
    if not should_post_pair_reconnect(mac):
        return
    cancel_pending_device_task(pending_paired_reconnect, mac)
    paired_reconnect_attempts.setdefault(mac, 0)
    pending_paired_reconnect[mac] = schedule_timeout_seconds(PAIRED_RECONNECT_DELAY, reconnect_paired_device, mac)

def remember_pairing_device(device):
    global visibility_timer
    mac = device_path_to_mac(device)
    if not mac: return None
    cancel_pending_device_task(pending_unpaired_cleanup, mac)
    cancel_pending_device_task(pending_paired_reconnect, mac)
    cancel_pending_device_task(pending_disconnect_confirm, mac)
    cancel_pending_device_task(pending_connection_activation, mac)
    paired_reconnect_attempts.pop(mac, None)
    # Một pairing mới vô hiệu mọi trạng thái/bảo vệ của bond cũ.
    clear_post_pair_reconnect(mac)

    # Nếu BlueZ vẫn báo Paired=True/Connected=True từ khóa cũ, hạ nó về link tạm
    # ngay khi Agent1 bắt đầu pairing mới để không SoftVolume/audio quá sớm.
    if mac in connected_devices:
        connected_devices.pop(mac, None)
        last_connected_at.pop(mac, None)
        if is_actually_connected(mac):
            pre_pair_connected.add(mac)
        if visibility_timer:
            cancel_scheduled_source(visibility_timer)
            visibility_timer = None
        if not connected_devices:
            set_visibility(True)

    if active_playback_device == mac or mac in pending_audio_start:
        log(f"Pairing bắt đầu, tạm dừng BlueALSA để tránh tranh PCM: {device_info_str(mac)}")
        stop_bluealsa_playback()
    if mac in pairing_devices:
        # Một phiên pairing có thể gọi nhiều callback Agent1. Gia hạn thời gian
        # từ callback mới nhất nhưng không tạo thêm timer kiểm tra trùng lặp.
        pairing_started_at[mac] = time.time()
        return mac
    pairing_devices.add(mac)
    pairing_started_at[mac] = time.time()
    schedule_timeout_seconds(PAIRING_VALIDATE_DELAY, validate_pairing_device, mac)
    log(f"Bắt đầu ghép đôi với thiết bị: {device_info_str(mac)}")
    return mac

def activate_paired_connection(mac, path=None, now=None):
    """Chỉ công nhận kết nối hoàn chỉnh sau khi cả Connected=True và Paired=True.

    BlueZ có thể phát Connected=True cho ACL/link tạm trước khi pairing hoàn tất.
    Link tạm đó không được phép kích hoạt audio, SoftVolume hay ẩn discoverable.
    """
    global visibility_timer
    if not mac or not is_paired(mac) or not is_actually_connected(mac):
        return False

    now = now or time.time()
    pre_pair_connected.discard(mac)
    cancel_pending_device_task(pending_unpaired_cleanup, mac)

    # Nếu đã được công nhận trước đó thì không tạo lại timer/log/SoftVolume.
    if mac in connected_devices:
        return True

    other_active = [
        m for m in connected_devices
        if m != mac and is_actually_connected(m) and is_paired(m)
    ]
    if other_active:
        log(f"Hệ thống đang giữ kết nối với thiết bị: {device_info_str(other_active[0])}, Từ chối kết nối mới tới thiết bị: {device_info_str(mac)}")
        try:
            device_path = path or f"{adapter_path()}/dev_{mac.upper().replace(':', '_')}"
            dev_obj = get_system_bus().get_object(BUS_NAME, device_path)
            dbus.Interface(dev_obj, "org.bluez.Device1").Disconnect()
        except Exception as error:
            log(f"DBus disconnect thất bại trên {BLUETOOTH_ADAPTER}: {error}")
        return False

    if visibility_timer:
        cancel_scheduled_source(visibility_timer)
        visibility_timer = None

    last_connected_at[mac] = now
    connected_devices[mac] = now
    log(f"Đã kết nối hoàn chỉnh với thiết bị: {device_info_str(mac)}")

    trust(mac)
    schedule_timeout_seconds(2, set_softvolume_true, mac, 1)
    visibility_timer = schedule_timeout_seconds(15, _delayed_hide_visibility, mac)

    if should_post_pair_reconnect(mac):
        schedule_post_pair_stable_check(mac)

    if mac not in pairing_devices and (ENABLE_AUDIO_AUTOPLAY or active_playback_device is None):
        start_bluealsa_playback(mac)
    return True

def validate_pairing_device(mac):
    if mac not in pairing_devices: return False
    if is_paired(mac):
        log(f"Xác nhận đã ghép đôi với thiết bị: {device_info_str(mac)}")
        pairing_devices.discard(mac)
        pairing_started_at.pop(mac, None)
        arm_post_pair_reconnect(mac)
        if is_actually_connected(mac):
            activate_paired_connection(mac)
        else:
            trust(mac)
            schedule_post_pair_disconnect_confirm(mac)
        return False
    if (time.time() - pairing_started_at.get(mac, time.time())) < PAIRING_MAX_WAIT: return True
    pairing_devices.discard(mac)
    pairing_started_at.pop(mac, None)
    log(f"Hết thời gian chờ ghép đôi với thiết bị: {device_info_str(mac)}")
    return False

def watchdog(interface, changed, invalidated, path):
    global visibility_timer
    mac = device_path_to_mac(path)
    if not mac: return

    if "Alias" in changed or "Name" in changed:
        name = str(changed.get("Alias", changed.get("Name", "")) or "")
        if name:
            device_names_cache[mac] = name

    if "Paired" in changed:
        paired = bool(changed["Paired"])
        if paired:
            cancel_pending_device_task(pending_unpaired_cleanup, mac)
            cancel_pending_device_task(pending_connection_activation, mac)
            pairing_devices.discard(mac)
            pairing_started_at.pop(mac, None)
            arm_post_pair_reconnect(mac)
            if is_actually_connected(mac):
                activate_paired_connection(mac, path=path)
            else:
                trust(mac)
                schedule_post_pair_disconnect_confirm(mac)
        else:
            # Thiết bị bị unpair: bỏ ngay mọi state/audio/reconnect cũ.
            clear_post_pair_reconnect(mac)
            cancel_pending_device_task(pending_connection_activation, mac)
            if visibility_timer:
                cancel_scheduled_source(visibility_timer)
                visibility_timer = None
            if mac in connected_devices:
                connected_devices.pop(mac, None)
                last_connected_at.pop(mac, None)
                if is_actually_connected(mac):
                    pre_pair_connected.add(mac)
                if active_playback_device == mac:
                    switch_to_next_device()
                if not connected_devices:
                    set_visibility(True)

    if "Connected" in changed:
        connected = bool(changed["Connected"])
        now = time.time()
        if connected:
            cancel_pending_device_task(pending_unpaired_cleanup, mac)
            cancel_pending_device_task(pending_disconnect_confirm, mac)
            paired_reconnect_attempts.pop(mac, None)

            # Connected=True trước Paired=True thường chỉ là ACL/link tạm phục vụ
            # pairing. Không đưa vào connected_devices, không SoftVolume/audio,
            # và cũng không ẩn discoverable.
            if not is_paired(mac):
                pre_pair_connected.add(mac)
                log(f"Đã thiết lập liên kết Bluetooth tạm, đang chờ ghép đôi: {device_info_str(mac)}")
                return

            # Nếu đây là reconnect của chính phiên pairing mới thì có thể công nhận
            # ngay. Với bond paired cũ, chờ vài giây để xem BlueZ có bắt đầu pairing
            # lại do khóa không còn hợp lệ hay không.
            if should_post_pair_reconnect(mac, now=now):
                activate_paired_connection(mac, path=path, now=now)
            else:
                schedule_connection_activation(mac, path=path)
        else:
            cancel_pending_device_task(pending_connection_activation, mac)
            cancel_pending_device_task(pending_post_pair_stable, mac)
            was_pre_pair = mac in pre_pair_connected
            pre_pair_connected.discard(mac)

            if was_pre_pair and mac not in connected_devices:
                log(f"Liên kết Bluetooth tạm trước pairing đã ngắt: {device_info_str(mac)}")
            elif mac in connected_devices:
                log(f"Tín hiệu ngắt kết nối từ: {device_info_str(mac)}")

            if visibility_timer and mac in connected_devices:
                cancel_scheduled_source(visibility_timer)
                visibility_timer = None

            if not is_paired(mac) and mac not in pairing_devices:
                schedule_unpaired_cleanup(mac)

            if mac in connected_devices:
                last_conn = last_connected_at.get(mac, 0)
                duration = now - last_conn
                connected_devices.pop(mac, None)
                softvolume_enabled.discard(mac)
                default_volume_applied.discard(mac)
                log(f"Đã ngắt kết nối hoàn toàn tới thiết bị: {device_info_str(mac)}")

                if not connected_devices:
                    set_visibility(True)

                if (duration < DISCONNECT_REMOVE_THRESHOLD and mac not in pairing_devices and not is_paired(mac)):
                    log(
                        f"Kết nối chưa ghép đôi bị ngắt sớm ({duration:.1f}s), "
                        f"sẽ dọn thiết bị sau {UNPAIRED_CLEANUP_DELAY}s nếu pairing không tiếp tục: {mac}"
                    )

                if should_post_pair_reconnect(mac, now=now):
                    paired_age = now - paired_completed_at.get(mac, now)
                    log(
                        f"Phát hiện mất kết nối sau pairing ({paired_age:.1f}s), "
                        f"xác nhận lại sau {DISCONNECT_CONFIRM_DELAY}s: {device_info_str(mac)}"
                    )
                    schedule_post_pair_disconnect_confirm(mac)

                if active_playback_device == mac:
                    switch_to_next_device()

def _delayed_hide_visibility(mac):
    global visibility_timer
    if mac in connected_devices and is_actually_connected(mac) and is_paired(mac):
        set_visibility(False)
    visibility_timer = None
    return False

def interfaces_added(path, interfaces):
    if "org.bluez.Device1" in interfaces:
        mac = device_path_to_mac(path)
        if mac:
            # Chỉ ghi nhận discovery. Phiên pairing chỉ bắt đầu khi BlueZ gọi
            # Agent1, tránh hết timeout trước khi người dùng bấm ghép đôi.
            log(f"Thiết bị mới phát hiện: {device_info_str(mac)}")

def interfaces_removed(path, interfaces):
    if "org.bluez.Device1" in interfaces:
        mac = device_path_to_mac(path)
        if mac:
            was_active = active_playback_device == mac
            log(f"Thiết bị đã bị xóa: {device_info_str(mac)}")
            clear_device_state(mac)
            if was_active:
                switch_to_next_device()

class Agent(dbus.service.Object):
    def __init__(self, bus, path):
        super().__init__(bus, path)

    def _reject_if_busy(self, device):
        mac = device_path_to_mac(device)
        if any(m != mac and is_actually_connected(m) for m in connected_devices):
            log(f"Hệ thống đang bận, từ chối yêu cầu từ thiết bị: {mac}")
            raise dbus.exceptions.DBusException("org.bluez.Error.Rejected")

    def _prepare_pairing(self, device):
        if device_path_to_mac(device) is None:
            log(f"Từ chối yêu cầu pairing ngoài adapter {BLUETOOTH_ADAPTER}: {device}")
            raise dbus.exceptions.DBusException("org.bluez.Error.Rejected")
        self._reject_if_busy(device)
        return remember_pairing_device(device)

    @dbus.service.method("org.bluez.Agent1", in_signature="", out_signature="")
    def Release(self):
        log("BlueZ đã giải phóng Bluetooth Agent")

    @dbus.service.method("org.bluez.Agent1", in_signature="o", out_signature="s")
    def RequestPinCode(self, device):
        mac = self._prepare_pairing(device)
        log(f"Cung cấp PIN {DEFAULT_PIN_CODE} cho thiết bị: {device_info_str(mac)}")
        return DEFAULT_PIN_CODE

    @dbus.service.method("org.bluez.Agent1", in_signature="os", out_signature="")
    def DisplayPinCode(self, device, pincode):
        mac = self._prepare_pairing(device)
        log(f"Mã PIN cho thiết bị {device_info_str(mac)}: {pincode}")

    @dbus.service.method("org.bluez.Agent1", in_signature="o", out_signature="u")
    def RequestPasskey(self, device):
        mac = self._prepare_pairing(device)
        log(f"Cung cấp passkey {DEFAULT_PASSKEY:06d} cho thiết bị: {device_info_str(mac)}")
        return dbus.UInt32(DEFAULT_PASSKEY)

    @dbus.service.method("org.bluez.Agent1", in_signature="ouq", out_signature="")
    def DisplayPasskey(self, device, passkey, entered):
        mac = self._prepare_pairing(device)
        log(f"Passkey cho thiết bị {device_info_str(mac)}: {int(passkey):06d}, đã nhập {int(entered)} ký tự")

    @dbus.service.method("org.bluez.Agent1", in_signature="os", out_signature="")
    def AuthorizeService(self, device, uuid):
        mac = device_path_to_mac(device)
        if mac is None:
            log(f"Từ chối service ngoài adapter {BLUETOOTH_ADAPTER}: {device}")
            raise dbus.exceptions.DBusException("org.bluez.Error.Rejected")
        self._reject_if_busy(device)
        # AuthorizeService có thể được gọi khi profile A2DP/AVRCP kết nối lại
        # trên thiết bị đã paired; không được coi đây là một phiên pairing mới.
        if is_paired(mac):
            trust(mac)
        log(f"Cho phép dịch vụ Bluetooth {uuid} từ: {device_info_str(mac)}")

    @dbus.service.method("org.bluez.Agent1", in_signature="ou", out_signature="")
    def RequestConfirmation(self, device, passkey):
        mac = self._prepare_pairing(device)
        log(f"Xác nhận passkey {int(passkey):06d} cho thiết bị: {device_info_str(mac)}")

    @dbus.service.method("org.bluez.Agent1", in_signature="o", out_signature="")
    def RequestAuthorization(self, device):
        mac = self._prepare_pairing(device)
        if mac: trust(mac)

    @dbus.service.method("org.bluez.Agent1", in_signature="", out_signature="")
    def Cancel(self):
        log("BlueZ đã hủy yêu cầu ghép đôi hiện tại")
        cancelled_devices = list(pairing_devices)
        pairing_devices.clear()
        pairing_started_at.clear()
        for mac in cancelled_devices:
            schedule_unpaired_cleanup(mac)

def request_shutdown(signum=None, _frame=None):
    global shutdown_requested
    if shutdown_requested:
        return
    shutdown_requested = True
    if signum is not None:
        log(f"Nhận tín hiệu dừng {signum}, đang đóng Bluetooth Agent...")
    if main_loop is not None:
        try:
            main_loop.quit()
        except Exception:
            pass

def cleanup_agent():
    global agent_registered, visibility_timer
    for source_id in list(scheduled_sources):
        cancel_scheduled_source(source_id)
    scheduled_sources.clear()
    pending_unpaired_cleanup.clear()
    pending_paired_reconnect.clear()
    paired_reconnect_attempts.clear()
    pending_disconnect_confirm.clear()
    pending_connection_activation.clear()
    post_pair_reconnect_active.clear()
    pending_post_pair_stable.clear()
    pending_post_pair_expiry.clear()
    paired_completed_at.clear()
    pre_pair_connected.clear()
    pending_audio_start.clear()
    last_audio_start_at.clear()
    softvolume_enabled.clear()
    default_volume_applied.clear()
    visibility_timer = None
    stop_bluealsa_playback()
    if agent_registered and agent_manager is not None:
        try:
            agent_manager.UnregisterAgent(AGENT_PATH)
            log("Đã hủy đăng ký Bluetooth Agent khỏi BlueZ")
        except Exception as error:
            log(f"Không thể hủy đăng ký Bluetooth Agent: {error}")
        finally:
            agent_registered = False

if __name__ == "__main__":
    dbus.mainloop.glib.DBusGMainLoop(set_as_default=True)
    bus = get_system_bus()
    try:
        select_available_adapter(bus)
        log(f"Sử dụng Bluetooth adapter: {BLUETOOTH_ADAPTER}")
        disable_unselected_adapters(bus)
        if ENABLE_PASSKEY_AUTH:
            Register_Agent = "KeyboardDisplay"
        else:
            Register_Agent = "NoInputNoOutput"
        agent_manager = dbus.Interface(bus.get_object(BUS_NAME, "/org/bluez"), "org.bluez.AgentManager1")
        agent = Agent(bus, AGENT_PATH)
        agent_manager.RegisterAgent(AGENT_PATH, Register_Agent)
        agent_registered = True
        agent_manager.RequestDefaultAgent(AGENT_PATH)

        signal.signal(signal.SIGTERM, request_shutdown)
        signal.signal(signal.SIGINT, request_shutdown)

        get_adapter_properties().Set("org.bluez.Adapter1", "Powered", dbus.Boolean(True))
        om = dbus.Interface(bus.get_object(BUS_NAME, "/"), "org.freedesktop.DBus.ObjectManager")

        # Không dùng PCM A2DP để xác định Bluetooth còn kết nối: điện thoại có
        # thể Connected nhưng đang idle/chưa phát nhạc. Nếu có hcitool/btmgmt,
        # dùng ACL kernel để loại Device1.Connected stale; nếu không thì fallback D-Bus.
        startup_kernel_connections = get_kernel_connected_macs()
        for path, interfaces in om.GetManagedObjects().items():
            if "org.bluez.Device1" in interfaces and interfaces["org.bluez.Device1"].get("Connected"):
                mac = device_path_to_mac(path)
                if not mac:
                    continue
                dev_props = interfaces["org.bluez.Device1"]
                if not bool(dev_props.get("Paired")):
                    pre_pair_connected.add(mac)
                    log(f"Bỏ qua Connected chưa paired khi khởi động: {device_info_str(mac)}")
                    continue
                if startup_kernel_connections is not None and mac.upper() not in startup_kernel_connections:
                    log(f"Bỏ qua trạng thái Connected cũ của BlueZ vì kernel không còn ACL: {device_info_str(mac)}")
                    continue
                connected_devices[mac] = time.time()
                last_connected_at[mac] = time.time()
                log(f"Khôi phục kết nối Bluetooth đang hoạt động: {device_info_str(mac)}")

        set_visibility(len(connected_devices) == 0)
        if connected_devices:
            start_bluealsa_playback(next(iter(connected_devices)))

        bus.add_signal_receiver(watchdog, dbus_interface="org.freedesktop.DBus.Properties", signal_name="PropertiesChanged", path_keyword="path", arg0="org.bluez.Device1")
        bus.add_signal_receiver(interfaces_added, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesAdded", bus_name=BUS_NAME)
        bus.add_signal_receiver(interfaces_removed, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesRemoved", bus_name=BUS_NAME)
        # BlueALSA PCM lifecycle: bật SoftVolume và mức dự phòng ngay khi PCM xuất hiện.
        bus.add_signal_receiver(bluealsa_interfaces_added, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesAdded", bus_name="org.bluealsa")
        bus.add_signal_receiver(bluealsa_interfaces_removed, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesRemoved", bus_name="org.bluealsa")
        schedule_timeout_seconds(5, playback_health_check)

        log("VBot Bluetooth Agent: Khởi động thành công")
        main_loop = GLib.MainLoop()
        main_loop.run()
    except Exception as e:
        log(f"Lỗi khởi động Agent: {e}")
    finally:
        request_shutdown()
        cleanup_agent()
