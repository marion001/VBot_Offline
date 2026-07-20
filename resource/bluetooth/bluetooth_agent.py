#!/usr/bin/env python3

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
from datetime import datetime
from collections import OrderedDict

#Tên DBus của BlueZ và đường dẫn Agent
BUS_NAME = "org.bluez"
AGENT_PATH = "/com/vbot/agent"
BLUETOOTH_ADAPTER = os.environ.get("VBOT_BLUETOOTH_ADAPTER", "hci0")
ENABLE_PASSKEY_AUTH = True  # True bật xác thực mã (KeyboardDisplay), False tắt (NoInputNoOutput)
DEFAULT_PIN_CODE = "0000"
DEFAULT_PASSKEY = 0

#Thời gian cấu hình ghép đôi
PAIRING_VALIDATE_DELAY = 1  # giây: chu kỳ kiểm tra pairing, đảm bảo MAX_WAIT chính xác
PAIRING_MAX_WAIT = 10  # giây: chờ tối đa để pairing hoàn thành
COMMAND_TIMEOUT = 10

#Ngưỡng timeout disconnect để quyết định xóa thiết bị stale
DISCONNECT_REMOVE_THRESHOLD = 10   # giây: nếu disconnect sớm hơn mức này thì remove
ENABLE_AUDIO_AUTOPLAY = False   # True để tự động nhận âm thanh ở kết nối mới khi thiết bị mới kết nối, False để tắt sẽ nhận âm thanh ở thiết bị kết nối đầu tiên

#nhớ trạng thái tạm thời
pairing_devices = set()  # thiết bị đang trong flow pairing
pairing_started_at = {}  # thời điểm bắt đầu pairing cho mỗi MAC
last_connected_at = {}  # thời điểm connect thành công lần cuối của mỗi MAC

connected_devices = OrderedDict()   #tập hợp các MAC đang kết nối
visibility_timer = None 

active_playback_device = None  # MAC của thiết bị đang phát âm thanh
active_playback_process = None  # process ID của bluealsa-aplay đang chạy
playback_generation = 0  # vô hiệu hóa callback delayed-start cũ
playback_lock = threading.RLock()
device_names_cache = {}  # cache tên thiết bị để tránh gọi bluetoothctl info nhiều lần
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
        proc = subprocess.run(
            cmd,
            shell=False,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            check=False,
            timeout=timeout,
        )
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
    if not path or "dev_" not in path: return None
    return path.split("dev_", 1)[1].replace("_", ":").upper()

_system_bus = None
def get_system_bus():
    global _system_bus
    if _system_bus is None: _system_bus = dbus.SystemBus()
    return _system_bus

def is_actually_connected(mac):
    try:
        bus = get_system_bus()
        obj = bus.get_object(BUS_NAME, f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_{mac.upper().replace(':', '_')}")
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        return bool(props.Get("org.bluez.Device1", "Connected"))
    except Exception:
        return False

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

#Bật SoftVolume trực tiếp cho thiết bị BlueALSA
def set_softvolume_true(mac):
    if not mac: return False
    bluealsa_path = f"/org/bluealsa/{BLUETOOTH_ADAPTER}/dev_{mac.replace(':', '_')}/a2dpsnk/source"
    log(f"Đang bật SoftVolume cho: {bluealsa_path}")
    try:
        #Sử dụng lệnh shell busctl trực tiếp để tránh lỗi DBus Interface cache trong Python
        subprocess.run(
            ["busctl", "set-property", "org.bluealsa", bluealsa_path, "org.bluealsa.PCM1", "SoftVolume", "b", "true"],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            check=False,
            timeout=COMMAND_TIMEOUT,
        )

        #busctl set-property hoàn tất đồng bộ; kiểm tra ngay để không chặn
        #GLib main loop bằng time.sleep().
        result = subprocess.run(
            ["busctl", "get-property", "org.bluealsa", bluealsa_path, "org.bluealsa.PCM1", "SoftVolume"],
            capture_output=True,
            text=True,
            check=False,
            timeout=COMMAND_TIMEOUT,
        )
        if "true" in result.stdout.lower():
            log(f"Xác nhận kiểm tra: SoftVolume đã bật thành công cho {mac}")
        else:
            log(f"Cảnh báo: SoftVolume cho {mac} vẫn là false")
    except Exception as e:
        log(f"Lỗi khi set SoftVolume cho {mac}: {e}")

    #Trả về False để GLib không lặp lại hàm này
    return False

def get_info(mac): return run(f"bluetoothctl info {mac}")

def is_paired(mac):
    try:
        bus = get_system_bus()
        obj = bus.get_object(BUS_NAME, f"/org/bluez/{BLUETOOTH_ADAPTER}/dev_{mac.upper().replace(':', '_')}")
        props = dbus.Interface(obj, "org.freedesktop.DBus.Properties")
        return bool(props.Get("org.bluez.Device1", "Paired"))
    except Exception:
        pass
    return "Paired: yes" in get_info(mac)

def trust(mac):
    if mac:
        log(f"Đánh dấu trust tin cậy thiết bị: {device_info_str(mac)}")
        run(f"bluetoothctl trust {mac}")

def is_running(proc): return proc is not None and proc.poll() is None

#chỉ được phép dừng process do agent này tạo.
def stop_bluealsa_playback():
    global active_playback_process, active_playback_device, playback_generation
    with playback_lock:
        playback_generation += 1
        process = active_playback_process
        device = active_playback_device
        active_playback_process = None
        active_playback_device = None
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

def start_bluealsa_playback(mac):
    global active_playback_process, active_playback_device, playback_generation
    if not mac: return
    if (active_playback_device == mac and is_running(active_playback_process)): return
    
    #Chỉ cho phép âm thanh nếu đã ghép đôi
    if not is_paired(mac):
        log(f"Chờ thiết bị {mac} ghép đôi xong mới mở âm thanh...")
        return

    stop_bluealsa_playback()
    log(f"Cho phép nhận âm thanh từ thiết bị: {device_info_str(mac)}")
    with playback_lock:
        active_playback_device = mac
        playback_generation += 1
        generation = playback_generation
    try:
        schedule_timeout_seconds(1, _delayed_start_audio, mac, generation)
    except Exception as error:
        log(f"Không thể lên lịch phát BlueALSA cho {mac}: {error}")

def _delayed_start_audio(mac, generation):
    global active_playback_process
    with playback_lock:
        if (shutdown_requested
                or generation != playback_generation
                or active_playback_device != mac):
            return False
    if not is_actually_connected(mac) or not is_paired(mac):
        with playback_lock:
            if generation == playback_generation:
                active_playback_process = None
        return False
    try:
        process = subprocess.Popen(
            ["bluealsa-aplay", mac],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
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
    if active_playback_device != primary:
        if is_paired(primary):
            log(f"Thiết lập nhận âm thanh ở thiết bị: {device_info_str(primary)}")
            start_bluealsa_playback(primary)

#Chỉ khởi động lại tiến trình phát lại đã chọn nếu bị thoát đột ngột
def playback_health_check():
    if shutdown_requested:
        return False
    device = active_playback_device
    if (device and is_actually_connected(device) and is_paired(device)
            and not is_running(active_playback_process)):
        log(f"bluealsa-aplay đã dừng ngoài ý muốn, khởi động lại cho: {device_info_str(device)}")
        start_bluealsa_playback(device)
    return True

def set_visibility(visible):
    if visible:
        run("bluetoothctl pairable on")
        run("bluetoothctl discoverable on")
        log("Trạng thái Bluetooth: Đã hiển thị tên thiết bị (Cho phép các thiết bị khác tìm thấy Bluetooth)")
    else:
        run("bluetoothctl discoverable off")
        run("bluetoothctl pairable off")
        log("Trạng thái Bluetooth: Đã ẩn tên thiết bị (Để duy trì 1 kết nối với thiết bị hiện tại)")

def clear_device_state(mac):
    if not mac: return
    pairing_devices.discard(mac)
    pairing_started_at.pop(mac, None)
    connected_devices.pop(mac, None)
    device_names_cache.pop(mac, None)

def remove_device(mac):
    if not mac: return
    log(f"Tiến hành dọn dẹp thiết bị kết nối lỗi: {device_info_str(mac)}")
    run(f"bluetoothctl remove {mac}")

def remember_pairing_device(device):
    mac = device_path_to_mac(device)
    if not mac or mac in pairing_devices: return mac
    pairing_devices.add(mac)
    pairing_started_at.setdefault(mac, time.time())
    schedule_timeout_seconds(PAIRING_VALIDATE_DELAY, validate_pairing_device, mac)
    log(f"Bắt đầu ghép đôi với thiết bị: {device_info_str(mac)}")
    return mac

def validate_pairing_device(mac):
    if mac not in pairing_devices: return False
    if is_paired(mac):
        log(f"Xác nhận đã ghép đôi với thiết bị: {device_info_str(mac)}")
        pairing_devices.discard(mac); trust(mac)
        if is_actually_connected(mac): start_bluealsa_playback(mac)
        return False
    if (time.time() - pairing_started_at.get(mac, time.time())) < PAIRING_MAX_WAIT: return True
    pairing_devices.discard(mac)
    return False

def watchdog(interface, changed, invalidated, path):
    global visibility_timer
    mac = device_path_to_mac(path)
    if not mac: return

    if "Alias" in changed or "Name" in changed:
        name = str(changed.get("Alias", changed.get("Name", "")) or "")
        if name:
            device_names_cache[mac] = name

    if "Paired" in changed and bool(changed["Paired"]): 
        trust(mac)
        if is_actually_connected(mac): start_bluealsa_playback(mac)

    if "Connected" in changed:
        connected = bool(changed["Connected"])
        now = time.time()
        if connected:
            other_active = [m for m in connected_devices if m != mac and is_actually_connected(m)]
            if other_active:
                log(f"Hệ thống đang giữ kết nối với thiết bị: {device_info_str(other_active[0])}, Từ chối kết nối mới tới thiết bị: {device_info_str(mac)}")
                try:
                    dev_obj = get_system_bus().get_object(BUS_NAME, path)
                    dbus.Interface(dev_obj, "org.bluez.Device1").Disconnect()
                except Exception as error:
                    log(f"DBus disconnect thất bại ({error}), dùng bluetoothctl")
                    run(f"bluetoothctl disconnect {mac}")
                return

            if visibility_timer:
                cancel_scheduled_source(visibility_timer)
                visibility_timer = None

            last_connected_at[mac] = now
            connected_devices[mac] = now
            log(f"Đã kết nối với thiết bị: {device_info_str(mac)}")

            #Bật SoftVolume sau 2 giây
            schedule_timeout_seconds(2, set_softvolume_true, mac)

            visibility_timer = schedule_timeout_seconds(15, _delayed_hide_visibility, mac)

            if is_paired(mac): 
                trust(mac)
                if ENABLE_AUDIO_AUTOPLAY or active_playback_device is None:
                    start_bluealsa_playback(mac)
            else:
                log(f"Thiết bị chưa ghép đôi, chờ hoàn tất kết nối trước khi nhận âm thanh: {mac}")
        else:
            log(f"Tín hiệu ngắt kết nối từ: {device_info_str(mac)}")
            if visibility_timer:
                cancel_scheduled_source(visibility_timer)
                visibility_timer = None

            if mac in connected_devices:
                last_conn = last_connected_at.get(mac, 0)
                duration = now - last_conn
                connected_devices.pop(mac, None)
                log(f"Đã ngắt kết nối hoàn toàn tới thiết bị: {device_info_str(mac)}")
                
                if not connected_devices: set_visibility(True)

                if duration < DISCONNECT_REMOVE_THRESHOLD and not is_paired(mac):
                    log(f"Kết nối không ổn định, thời gian giữ quá ngắn ({duration:.1f}s), tiến hành dọn dẹp {mac}")
                    remove_device(mac)

                if active_playback_device == mac: switch_to_next_device()

def _delayed_hide_visibility(mac):
    global visibility_timer
    if is_actually_connected(mac):
        set_visibility(False)
    visibility_timer = None
    return False

def interfaces_added(path, interfaces):
    if "org.bluez.Device1" in interfaces:
        mac = device_path_to_mac(path)
        if mac: log(f"Thiết bị mới phát hiện: {device_info_str(mac)}"); remember_pairing_device(path)

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
        log(
            f"Passkey cho thiết bị {device_info_str(mac)}: "
            f"{int(passkey):06d}, đã nhập {int(entered)} ký tự"
        )

    @dbus.service.method("org.bluez.Agent1", in_signature="os", out_signature="")
    def AuthorizeService(self, device, uuid):
        mac = self._prepare_pairing(device)
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

        run("bluetoothctl power on")
        om = dbus.Interface(bus.get_object(BUS_NAME, "/"), "org.freedesktop.DBus.ObjectManager")
        for path, interfaces in om.GetManagedObjects().items():
            if "org.bluez.Device1" in interfaces and interfaces["org.bluez.Device1"].get("Connected"):
                mac = device_path_to_mac(path)
                if mac: connected_devices[mac] = time.time(); last_connected_at[mac] = time.time()

        set_visibility(len(connected_devices) == 0)
        if connected_devices: start_bluealsa_playback(next(iter(connected_devices)))

        bus.add_signal_receiver(watchdog, dbus_interface="org.freedesktop.DBus.Properties", signal_name="PropertiesChanged", path_keyword="path", arg0="org.bluez.Device1")
        bus.add_signal_receiver(interfaces_added, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesAdded")
        bus.add_signal_receiver(interfaces_removed, dbus_interface="org.freedesktop.DBus.ObjectManager", signal_name="InterfacesRemoved")
        schedule_timeout_seconds(5, playback_health_check)

        log("VBot Bluetooth Agent: Khởi động thành công")
        main_loop = GLib.MainLoop()
        main_loop.run()
    except Exception as e:
        log(f"Lỗi khởi động Agent: {e}")
    finally:
        request_shutdown()
        cleanup_agent()
