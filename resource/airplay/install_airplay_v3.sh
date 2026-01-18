#!/bin/bash

# ===================================================================================
# Shairport-Sync AirPlay 2 ROBUST Installer - ENHANCED VERSION 3.0
#
# Tailored for: Raspberry Pi (Zero 2/3/4/5) with USB DAC
# Version: 3.0 - Production Ready
# Features:
#   - Comprehensive error handling with rollback capability
#   - Dependency validation before installation
#   - Build failure recovery
#   - Audio device validation
#   - Service health checks
#   - Firewall configuration
#   - Latest package versions
# ===================================================================================

set -eo pipefail   # Thoát khi xảy ra lỗi và sự cố
IFS=$'\n\t'        # Tách từ an toàn hơn

# --- Biến toàn cục ---
SCRIPT_VERSION="3.0"
LOG_FILE="/tmp/airplay_install_$(date +%Y%m%d_%H%M%S).log"
BACKUP_DIR="/tmp/airplay_backup_$(date +%Y%m%d_%H%M%S)"
INSTALLATION_FAILED=0

#Các biến cấu hình âm thanh
audio_device=""
audio_device_plug=""
card_number=""
device_number=""
mixer_control=""
selected_device=""
airplay_name=""
disable_wifi_pm=false

#Xử lý dọn dẹp
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ] && [ $INSTALLATION_FAILED -eq 1 ]; then
        cecho "red" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        cecho "red" "   Cài đặt thất bại - Đang dọn dẹp"
        cecho "red" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

        #Dừng các dịch vụ nếu chúng đã được khởi động.
        sudo systemctl stop shairport-sync 2>/dev/null || true
        sudo systemctl stop nqptp 2>/dev/null || true

        #Khôi phục bản sao lưu nếu có.
        if [ -d "$BACKUP_DIR" ]; then
            cecho "yellow" "Khôi phục cấu hình ban đầu..."
            [ -f "$BACKUP_DIR/shairport-sync.conf" ] && \
                sudo cp "$BACKUP_DIR/shairport-sync.conf" /etc/shairport-sync.conf 2>/dev/null || true
        fi

        cecho "yellow" "Nhật ký cài đặt đã được lưu vào: $LOG_FILE"
        cecho "yellow" "Vui lòng kiểm tra nhật ký để biết thêm chi tiết.."
    fi

    #Dọn dẹp các thư mục xây dựng tạm thời
    rm -rf /tmp/nqptp /tmp/shairport-sync 2>/dev/null || true
}

trap cleanup EXIT ERR INT TERM

#Chức năng ghi nhật ký
log() {
    #Tạo tệp nhật ký nếu nó chưa tồn tại.
    touch "$LOG_FILE" 2>/dev/null || true
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

#Các hàm hỗ trợ
cecho() {
    local code="\033["
    case "$1" in
        "red")    color="${code}1;31m" ;;
        "green")  color="${code}1;32m" ;;
        "yellow") color="${code}1;33m" ;;
        "blue")   color="${code}1;34m" ;;
        "magenta") color="${code}1;35m" ;;
        *)        color="${code}0m" ;;
    esac
    echo -e "${color}$2\033[0m"
}

#Loading trong quá trình dài.
show_spinner() {
    local pid=$1
    local delay=0.1
    local spinstr='|/-\'
    while ps -p $pid > /dev/null 2>&1; do
        local temp=${spinstr#?}
        printf " [%c]  " "$spinstr"
        local spinstr=$temp${spinstr%"$temp"}
        sleep $delay
        printf "\b\b\b\b\b\b"
    done
    printf "    \b\b\b\b"
}

#Kiểm tra xem lệnh có tồn tại hay không.
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

#Kiểm tra xem dịch vụ có đang hoạt động bình thường hay không.
check_service() {
    local service_name=$1
    local max_retries=${2:-3}
    local retry=0

    while [ $retry -lt $max_retries ]; do
        if systemctl is-active --quiet "$service_name"; then
            cecho "green" "✓ $service_name đang chạy"
            return 0
        fi
        retry=$((retry + 1))
        [ $retry -lt $max_retries ] && sleep 2
    done

    cecho "red" "✗ $service_name không khởi động được sau $max_retries thử"
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "yellow" "   Thông tin chẩn đoán:"
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    echo "Trạng thái dịch vụ:" | tee -a "$LOG_FILE"
    sudo systemctl status "$service_name" --no-pager -l 2>&1 | tee -a "$LOG_FILE"
    echo
    echo "Nhật ký gần đây:" | tee -a "$LOG_FILE"
    sudo journalctl -u "$service_name" -n 30 --no-pager 2>&1 | tee -a "$LOG_FILE"
    echo
    cecho "yellow" "Kiểm tra tệp nhật ký để biết thêm chi tiết.: $LOG_FILE"
    return 1
}

#Xác thực quá trình cài đặt gói
validate_package() {
    local package=$1
    if dpkg -l "$package" 2>/dev/null | grep -q "^ii"; then
        return 0
    else
        cecho "red" "✗ Package $package Không cài đặt thành công"
        return 1
    fi
}

#Thay đổi thư mục an toàn
safe_cd() {
    cd "$1" || {
        cecho "red" "Không thể thay đổi thư mục: $1"
        exit 1
    }
}

#Thực hiện kiểm tra trước khi cài đặt
pre_flight_checks() {
    cecho "blue" "═══════════════════════════════════════"
    cecho "blue" "   Thực hiện kiểm tra trước khi cài đặt..."
    cecho "blue" "═══════════════════════════════════════"
    echo

    #Kiểm tra người dùng root
    if [ "$EUID" -eq 0 ]; then
        cecho "red" "❌ Lỗi: Không được chạy tập lệnh này với sudo hoặc với quyền root."
        cecho "yellow" "   Chạy Lệnh: bash install_airplay_v3.sh"
        exit 1
    fi

    #Kiểm tra xem người dùng có quyền sudo hay không.
    if ! sudo -n true 2>/dev/null; then
        cecho "yellow" "Kiểm tra quyền truy cập sudo..."
        if ! sudo true; then
            cecho "red" "❌ Tập lệnh này yêu cầu quyền truy cập sudo.."
            exit 1
        fi
    fi
    cecho "green" "✓ Đã xác nhận quyền truy cập Sudo"

    #Kiểm tra kết nối internet
    cecho "yellow" "Kiểm tra kết nối internet..."
    local test_hosts=("8.8.8.8" "1.1.1.1" "github.com")
    local connection_ok=0

    for host in "${test_hosts[@]}"; do
        #Sử dụng lệnh timeout để tránh bị treo.
        cecho "blue" "  Kiểm tra: $host..."
        timeout 8 ping -c 1 -W 5 "$host" >/dev/null 2>&1 || true
        local result=$?
        if [ $result -eq 0 ]; then
            connection_ok=1
            log "Kiểm tra kết nối Internet: Ping thành công. $host"
            cecho "green" "  ✓ Đã kết nối"
            break
        elif [ $result -eq 124 ]; then
            log "Kiểm tra kết nối Internet: Hết thời gian chờ ping $host"
            cecho "yellow" "  ✗ Timeout"
        else
            log "Kiểm tra kết nối Internet: Không thể ping $host (exit $result)"
            cecho "yellow" "  ✗ Thất bại"
        fi
    done

    if [ $connection_ok -eq 0 ]; then
        cecho "red" "❌ Không phát hiện thấy kết nối internet.."
        cecho "yellow" "   Khắc phục sự cố:"
        cecho "yellow" "   1. Kiểm tra xem Wi-Fi đã được kết nối chưa: iwconfig"
        cecho "yellow" "   2. Kiểm tra thủ công: ping -c 3 8.8.8.8"
        cecho "yellow" "   3. Kiểm tra DNS: ping -c 3 github.com"
        echo
        cecho "blue" "   Trạng thái mạng của bạn:"
        ip addr show wlan0 2>/dev/null | grep "inet " || echo "   Không có địa chỉ IP wlan0"
        echo
        read -p "Bỏ qua bước kiểm tra kết nối internet và tiếp tục? (y/N): " skip_check || true
        if [[ "$skip_check" =~ ^[Yy]$ ]]; then
            cecho "yellow" "⚠ Tiếp tục mà không kiểm tra kết nối internet (có thể sẽ thất bại sau này)"
            log "Người dùng đã bỏ qua bước kiểm tra kết nối internet."
        else
            exit 1
        fi
    else
        cecho "green" "✓ Kết nối internet ổn định"
    fi

    #Kiểm tra xem có đang chạy trên Raspberry Pi không.
    if [ ! -f /proc/device-tree/model ]; then
        cecho "yellow" "⚠ Cảnh báo: Đây có vẻ không phải là Raspberry Pi."
        read -p "Bạn có muốn tiếp tục chứ? (y/N): " continue_choice || true
        [[ ! "$continue_choice" =~ ^[Yy]$ ]] && exit 1
    else
        local pi_model
        pi_model=$(tr -d '\0' < /proc/device-tree/model)
        cecho "green" "✓ Đã phát hiện: $pi_model"
        #Cảnh báo trên các mẫu Pi cũ hơn
        if echo "$pi_model" | grep -qE "Pi Zero W|Pi 1"; then
            cecho "yellow" "⚠ Cảnh báo: $pi_model có thể không đủ khả năng để chạy AirPlay 2"
            cecho "yellow" "   Khuyến nghị: Raspberry Pi Zero 2 trở lên."
            read -p "Bạn có muốn tiếp tục? (y/N): " continue_choice || true
            [[ ! "$continue_choice" =~ ^[Yy]$ ]] && exit 1
        fi
    fi

    #Kiểm tra dung lượng ổ đĩa trống (cần ít nhất 1GB)
    local available_space
    available_space=$(df / | tail -1 | awk '{print $4}')
    if [ "$available_space" -lt 1000000 ]; then
        cecho "red" "❌ Không đủ dung lượng ổ đĩa. Cần ít nhất 1GB dung lượng trống.."
        cecho "yellow" "   Hiện có sẵn: $((available_space / 1024)) MB"
        exit 1
    fi
    cecho "green" "✓ Dung lượng ổ đĩa còn đủ: $((available_space / 1024)) MB"

    #Kiểm tra dung lượng bộ nhớ khả dụng
    local available_mem
    available_mem=$(free -m | awk '/^Mem:/{print $7}')
    if [ "$available_mem" -lt 100 ]; then
        cecho "yellow" "⚠ Cảnh báo: Bộ nhớ khả dụng thấp ($available_mem MB)"
        cecho "yellow" "   Hãy cân nhắc đóng các ứng dụng khác.."
    else
        cecho "green" "✓ Bộ nhớ khả dụng: $available_mem MB"
    fi

    #Kiểm tra các công cụ cơ bản cần thiết
    cecho "yellow" "Kiểm tra các công cụ cần thiết..."
    local required_tools=("git" "gcc" "make" "aplay" "amixer")
    local missing_tools=()

    for tool in "${required_tools[@]}"; do
        if ! command_exists "$tool"; then
            missing_tools+=("$tool")
        fi
    done

    if [ ${#missing_tools[@]} -gt 0 ]; then
        cecho "yellow" "⚠ Thiếu công cụ: ${missing_tools[*]}"
        cecho "yellow" "   Chúng sẽ được cài đặt cùng với các phần phụ thuộc.."
    else
        cecho "green" "✓ Tất cả các công cụ cơ bản đều có sẵn."
    fi

    echo
}

#Phát hiện và lựa chọn DAC USB
select_audio_device() {
    echo
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "yellow" "   Bước 1: Chọn thiết bị âm thanh"
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    cecho "cyan" "⏸  VUI LÒNG TRẢ LỜI YÊU CẦU NÀY ⏸"
    echo

    #Lấy danh sách tất cả các card âm thanh
    cecho "blue" "Đang quét tìm thiết bị âm thanh..."
    local all_cards
    all_cards=$(aplay -l 2>/dev/null | grep '^card' || true)

    if [ -z "$all_cards" ]; then
        cecho "red" "❌ Không phát hiện thấy bất kỳ thiết bị âm thanh nào.!"
        cecho "yellow" "   Hãy đảm bảo rằng USB DAC hoặc thiết bị âm thanh của bạn đã được kết nối đúng cách.."
        cecho "yellow" "   Hãy thử lệnh: lsusb hoặc aplay -l (để kiểm tra xem thiết bị USB có được nhận diện hay không)"
        exit 1
    fi

    #Hiển thị tất cả các thiết bị đã được phát hiện
    cecho "green" "Tôi đã tìm thấy những thiết bị âm thanh này.:"
    echo "$all_cards" | nl -w2 -s'. '
    echo

    #Lọc bỏ âm thanh tích hợp (bcm2835, Tai nghe, vc4-hdmi)
    mapfile -t external_devices < <(echo "$all_cards" | grep -iv 'bcm2835\|Headphones\|vc4-hdmi' || true)

    if [ ${#external_devices[@]} -eq 0 ]; then
        cecho "yellow" "⚠ Không phát hiện thấy DAC USB ngoài.!"
        cecho "yellow" "   Chỉ tìm thấy âm thanh tích hợp sẵn.."
        echo
        read -p "Bạn muốn sử dụng âm thanh tích hợp sẵn? (y/N): " use_builtin || true

        if [[ "$use_builtin" =~ ^[Yy]$ ]]; then
            mapfile -t external_devices < <(echo "$all_cards")
        else
            cecho "yellow" "   Vui lòng:"
            cecho "yellow" "   1. Kết nối DAC USB, hoặc thiết bị mạch âm thanh của bạn"
            cecho "yellow" "   2. Chờ 5 giây"
            cecho "yellow" "   3. Chạy lại đoạn mã này"
            exit 1
        fi
    fi

    #Tự động chọn nếu chỉ có một thiết bị
    if [ ${#external_devices[@]} -eq 1 ]; then
        cecho "green" "✓ Đã tìm thấy một thiết bị âm thanh, đang tự động chọn.:"
        cecho "magenta" "  → ${external_devices[0]}"
        selected_device="${external_devices[0]}"
    else
        #Nhiều thiết bị - cho phép người dùng lựa chọn
        cecho "yellow" "Tìm thấy ${#external_devices[@]} thiết bị âm thanh:"
        for i in "${!external_devices[@]}"; do
            echo "  [$i] ${external_devices[$i]}"
        done
        echo

        local device_choice
        while true; do
            read -p "Nhập số ID âm thanh [0-$((${#external_devices[@]}-1))]: " device_choice || true

            if [[ "$device_choice" =~ ^[0-9]+$ ]] && [ "$device_choice" -lt "${#external_devices[@]}" ]; then
                break
            fi
            cecho "red" "Lựa chọn không hợp lệ. Vui lòng thử lại.."
        done

        selected_device="${external_devices[$device_choice]}"
    fi

    #Trích xuất số thẻ và số thiết bị một cách đáng tin cậy hơn
    card_number=$(echo "$selected_device" | grep -oP 'card \K\d+' || echo "")
    device_number=$(echo "$selected_device" | grep -oP 'device \K\d+' || echo "0")

    if [ -z "$card_number" ]; then
        cecho "red" "❌ Không thể trích xuất số thẻ từ: $selected_device"
        exit 1
    fi

    audio_device="hw:$card_number,$device_number"
    audio_device_plug="plughw:$card_number,$device_number"

    cecho "green" "✓ Thiết bị âm thanh được đặt thành: $audio_device_plug"

    #Hãy kiểm tra xem thiết bị âm thanh có hoạt động bình thường hay không.
    cecho "blue" "Xác thực thiết bị âm thanh..."
    if aplay -D "$audio_device_plug" -l >/dev/null 2>&1; then
        cecho "green" "✓ Xác thực thiết bị âm thanh đã thành công."
    else
        cecho "red" "❌ Xác thực thiết bị âm thanh thất bại"
        cecho "yellow" "   Thiết bị có thể không hỗ trợ phát lại.."
        read -p "Continue anyway? (y/N): " continue_choice || true
        [[ ! "$continue_choice" =~ ^[Yy]$ ]] && exit 1
    fi

    #Tìm các nút điều khiển bộ trộn âm lượng khả dụng cho card này.
    cecho "blue" "Phát hiện các nút điều chỉnh âm lượng..."
    mapfile -t mixers < <(amixer -c "$card_number" scontrols 2>/dev/null | grep -oP "Simple mixer control '\K[^']+" || true)

    if [ ${#mixers[@]} -eq 0 ]; then
        cecho "yellow" "⚠ Không tìm thấy nút điều khiển bộ trộn. Chức năng điều chỉnh âm lượng sẽ bị vô hiệu hóa.."
        mixer_control=""
    else
        cecho "green" "Các nút điều khiển máy trộn có sẵn:"
        for mixer in "${mixers[@]}"; do
            echo "  - $mixer"
        done

        # Hãy cố gắng tìm bộ điều khiển trộn âm lượng tốt nhất
        mixer_control=""
        for preferred in "PCM" "Master" "Speaker" "Headphone" "Digital"; do
            for mixer in "${mixers[@]}"; do
                if [[ "$mixer" == "$preferred" ]]; then
                    mixer_control="$mixer"
                    break 2
                fi
            done
        done

        # Nếu không tìm thấy trộn âm lượng nào phù hợp, hãy sử dụng máy trộn đầu tiên.
        if [ -z "$mixer_control" ]; then
            mixer_control="${mixers[0]}"
        fi

        cecho "green" "✓ Điều khiển âm lượng: $mixer_control"
    fi
    echo
}

#Lấy tên AirPlay
get_airplay_name() {
    echo
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "yellow" "   Bước 2: Đặt tên cho thiết bị AirPlay của bạn"
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    cecho "cyan" "⏸  VUI LÒNG TRẢ LỜI YÊU CẦU NÀY ⏸"
    echo

    local hostname
    hostname=$(hostname)
    cecho "blue" "Đây là tên sẽ hiển thị trên iPhone/iPad của bạn."
    cecho "blue" "Ví dụ: Loa phòng khách, loa phòng ngủ, hệ thống âm thanh nhà bếp."
    echo
    cecho "green" ">>> "
    read -p "Nhập tên (hoặc nhấn Enter cho '$hostname AirPlay'): " airplay_name || true

    if [ -z "$airplay_name" ]; then
        airplay_name="$hostname AirPlay"
    fi

    #Làm sạch tên (loại bỏ các ký tự đặc biệt có thể gây ra sự cố)
    airplay_name=$(echo "$airplay_name" | sed 's/[^a-zA-Z0-9 _-]//g')

    cecho "green" "✓ Tên AirPlay: '$airplay_name'"
    echo
}

#Quản lý nguồn Wi-Fi
configure_wifi() {
    echo
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "yellow" "   Bước 3: Tối ưu hóa Wi-Fi"
    cecho "yellow" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    cecho "cyan" "⏸  VUI LÒNG TRẢ LỜI YÊU CẦU NÀY ⏸"
    echo

    # Check if Wi-Fi is actually being used
    if ! ip link show wlan0 &>/dev/null; then
        cecho "yellow" "⚠ Không phát hiện thấy giao diện Wi-Fi (không tìm thấy wlan0)"
        cecho "yellow" "   Bỏ qua tối ưu hóa Wi-Fi."
        disable_wifi_pm=false
        echo
        return
    fi

    cecho "blue" "Chế độ tiết kiệm năng lượng Wi-Fi có thể gây ra hiện tượng gián đoạn và ngắt quãng âm thanh."
    cecho "blue" "Việc tắt chức năng này đảm bảo quá trình phát lại diễn ra mượt mà và không bị gián đoạn.."
    echo
    cecho "green" ">>> "
    read -p "Tắt chế độ tiết kiệm điện Wi-Fi? (Y/n): " wifi_choice || true

    if [[ -z "$wifi_choice" || "$wifi_choice" =~ ^[Yy]$ ]]; then
        disable_wifi_pm=true
        cecho "green" "✓ Chức năng quản lý nguồn Wi-Fi sẽ bị vô hiệu hóa."
    else
        disable_wifi_pm=false
        cecho "yellow" "⚠ Giữ nguyên cài đặt Wi-Fi mặc định (có thể gây mất kết nối)"
    fi
    echo
}

#Cài đặt chính
main() {
    #Khởi tạo tệp nhật ký
    touch "$LOG_FILE" 2>/dev/null || LOG_FILE="/tmp/airplay_install_fallback.log"

    clear
    cecho "green" "╔═════════════════════════════════════════════════════╗"
    cecho "green" "║                                                     ║"
    cecho "green" "║     AirPlay 2 Installer for Raspberry Pi + DAC     ║"
    cecho "green" "║                  Version $SCRIPT_VERSION                        ║"
    cecho "green" "║                                                     ║"
    cecho "green" "╚═════════════════════════════════════════════════════╝"
    echo
    cecho "blue" "Trình cài đặt này sẽ biến Raspberry Pi của bạn thành một..."
    cecho "blue" "Bộ thu AirPlay 2 chất lượng cao. Chỉ cần làm theo hướng dẫn.!"
    echo
    cecho "yellow" "Nhật ký cài đặt: $LOG_FILE"
    echo

    log "=== Quá trình cài đặt AirPlay 2 đã bắt đầu. ==="
    log "Phiên bản chương trình: $SCRIPT_VERSION"
    log "Ngày: $(date)"
    log "Người dùng: $(whoami)"
    log "Hệ thống: $(uname -a)"
    echo

    #Kiểm tra xem có đang chạy tương tác SSH hay không
    if [ -t 0 ]; then
        read -p "Nhấn Enter để bắt đầu..." || true
    else
        cecho "yellow" "⚠ Đã phát hiện chế độ không tương tác - đang sử dụng cài đặt mặc định"
        sleep 2
    fi
    echo

    #Thực hiện tất cả các bước thiết lập
    pre_flight_checks
    select_audio_device
    get_airplay_name
    configure_wifi

    #Xác nhận
    echo
    echo
    cecho "magenta" "╔═════════════════════════════════════════════════════╗"
    cecho "magenta" "║                 CẤU HÌNH CÀI ĐẶT                    ║"
    cecho "magenta" "╚═════════════════════════════════════════════════════╝"
    echo
    cecho "yellow" "  📱 Tên AirPlay:        $airplay_name"
    cecho "yellow" "  🔊 Đầu ra âm thanh:        $audio_device_plug"
    cecho "yellow" "  🎚️  Điều khiển âm lượng:      ${mixer_control:-None (fixed volume)}"
    cecho "yellow" "  📡 Tắt tiếp kiệm điện Wi-Fi:    $disable_wifi_pm"
    echo
    cecho "blue" "Quá trình cài đặt sẽ mất từ ​​10 đến 30 phút tùy thuộc vào kiểu máy Raspberry Pi của bạn."
    cecho "blue" "(Pi Zero 2 sẽ chậm hơn, Pi 4/5 sẽ nhanh hơn.)"
    echo
    echo
    cecho "cyan" "⏸  XÁC NHẬN CUỐI CÙNG - NHẤN ENTER ĐỂ TIẾP TỤC ⏸"
    echo
    if [ -t 0 ]; then
        read -p "Nhấn Enter để bắt đầu cài đặt, hoặc Ctrl+C để hủy bỏ..." || true
    else
        cecho "yellow" "Tự động khởi động sau 5 giây (ở chế độ không tương tác)..."
        sleep 5
    fi
    echo

    INSTALLATION_FAILED=1  #Đánh dấu rằng quá trình cài đặt đã bắt đầu.

    #Tạo thư mục sao lưu
    mkdir -p "$BACKUP_DIR"
    [ -f /etc/shairport-sync.conf ] && cp /etc/shairport-sync.conf "$BACKUP_DIR/" 2>/dev/null || true

    #Cập nhật hệ thống
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Cập nhật các gói hệ thống..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Cập nhật danh sách gói..."

    if ! sudo apt-get update -qq 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Không thể cập nhật danh sách gói."
        exit 1
    fi

    #cecho "yellow" "Upgrading existing packages (this may take a few minutes)..."
    #if ! sudo apt-get upgrade -y 2>&1 | tee -a "$LOG_FILE"; then
        #cecho "yellow" "⚠ Package upgrade had issues, but continuing..."
    #fi

    cecho "green" "✓ Hệ thống đã được cập nhật"
    echo

    #Cài đặt các phần phụ thuộc
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "  Cài đặt các phần phụ thuộc..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Cài đặt các thư viện cần thiết để biên dịch..."

    local dependencies=(
        build-essential git autoconf automake libtool pkg-config
        libpopt-dev libconfig-dev libasound2-dev
        avahi-daemon libavahi-client-dev libssl-dev
        libsoxr-dev libplist-dev libsodium-dev
        libavutil-dev libavcodec-dev libavformat-dev
        uuid-dev libgcrypt20-dev xxd alsa-utils libmosquitto-dev mosquitto mosquitto-clients
    )

    if ! sudo apt-get install -y "${dependencies[@]}" 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Không thể cài đặt các phần phụ thuộc"
        exit 1
    fi

    #Xác thực các gói quan trọng
    local critical_packages=("build-essential" "git" "libasound2-dev" "avahi-daemon")
    for package in "${critical_packages[@]}"; do
        if ! validate_package "$package"; then
            cecho "red" "❌ Gói quan trọng $package chưa được cài đặt."
            exit 1
        fi
    done

    cecho "green" "✓ Các phần phụ thuộc đã được cài đặt"
    echo

    #Hãy đảm bảo rằng avahi-daemon đang chạy.
    if ! systemctl is-active --quiet avahi-daemon; then
        cecho "yellow" "Khởi động avahi-daemon..."
        sudo systemctl enable avahi-daemon
        sudo systemctl start avahi-daemon
    fi

    #Cài đặt NQPTP
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Cài đặt NQPTP (Hệ thống thời gian định thời)..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Sao chép kho lưu trữ NQPTP..."

    #Kiểm tra xem thư mục /tmp có thể ghi được hay không.
    if [ ! -w /tmp ]; then
        cecho "red" "❌ /tmp Thư mục không thể ghi"
        exit 1
    fi

    safe_cd /tmp
    rm -rf nqptp 2>/dev/null || true

    if ! git clone https://github.com/mikebrady/nqptp.git 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Không thể sao chép kho lưu trữ NQPTP."
        cecho "yellow" "   Nguyên nhân có thể:"
        cecho "yellow" "   - Không có kết nối internet"
        cecho "yellow" "   - GitHub đang gặp sự cố."
        cecho "yellow" "   - Tường lửa chặn truy cập"
        exit 1
    fi

    safe_cd nqptp
    log "Xây Dựng NQPTP..."

    if ! autoreconf -fi 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ NQPTP tự động cấu hình lại thất bại"
        exit 1
    fi

    if ! ./configure --with-systemd-startup 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ NQPTP cấu hình thất bại"
        exit 1
    fi

    if ! make -j"$(nproc)" 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ NQPTP biên dịch thất bại"
        exit 1
    fi

    if ! sudo make install 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ NQPTP cài đặt thất bại"
        exit 1
    fi

    #Xác minh xem tệp nhị phân đã được cài đặt chưa.
    if ! command_exists nqptp; then
        cecho "red" "❌ NQPTP Không tìm thấy tệp nhị phân sau khi cài đặt"
        cecho "yellow" "   Vị trí dự kiến: /usr/local/bin/nqptp"
        exit 1
    fi

    # Enable and start NQPTP
    sudo systemctl enable nqptp 2>&1 | tee -a "$LOG_FILE"
    sudo systemctl restart nqptp 2>&1 | tee -a "$LOG_FILE"
    sleep 3

    if ! check_service "nqptp"; then
        cecho "red" "❌ NQPTP Dịch vụ không khởi động được."
        exit 1
    fi
    echo

    #Cài đặt Shairport-Sync
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Cài đặt Shairport-Sync..."
    cecho "blue" "   (Quá trình này mất 10-20 phút trên các máy Raspberry Pi có cấu hình chậm hơn.)"
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Đang sao chép kho lưu trữ Shairport-Sync..."

    safe_cd /tmp
    rm -rf shairport-sync 2>/dev/null || true

    if ! git clone https://github.com/marion001/shairport-sync.git 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Không thể sao chép kho lưu trữ Shairport-Sync."
        cecho "yellow" "   Nguyên nhân có thể:"
        cecho "yellow" "   - Không có kết nối internet"
        cecho "yellow" "   - GitHub đang gặp sự cố."
        cecho "yellow" "   - Tường lửa chặn truy cập"
        exit 1
    fi

    safe_cd shairport-sync
    log "Xây dựng Shairport-Sync..."

    if ! autoreconf -fi 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Shairport-Sync tự động cấu hình lại thất bại"
        exit 1
    fi

    cecho "yellow" "Cấu hình bản dựng..."
    if ! ./configure --with-mqtt-client --sysconfdir=/etc --with-alsa --with-avahi \
        --with-ssl=openssl --with-soxr --with-systemd \
        --with-airplay-2 2>&1 | tee -a "$LOG_FILE"; then
        cecho "red" "❌ Cấu hình Shairport-Sync thất bại"
        exit 1
    fi

    cecho "yellow" "Đang biên dịch (hãy kiên nhẫn, quá trình này mất thời gian)..."
    log "Bắt đầu biên dịch với $(nproc) lõi..."

    #Chạy lệnh make ở chế độ nền để chúng ta có thể hiển thị biểu tượng chờ.
    local make_log="${LOG_FILE}.make"
    make -j"$(nproc)" > "$make_log" 2>&1 &
    local make_pid=$!
    show_spinner $make_pid

    #Chờ quá trình sản xuất hoàn tất và kiểm tra trạng thái.
    if ! wait $make_pid; then
        cecho "red" "❌ Quá trình biên dịch Shairport-Sync thất bại"
        cecho "yellow" "20 dòng cuối của nhật ký xây dựng:"
        tail -20 "$make_log" 2>/dev/null || echo "  (Tệp nhật ký không khả dụng)"
        exit 1
    fi
    cat "$make_log" >> "$LOG_FILE" 2>/dev/null || true

    cecho "yellow" "Cài đặt..."
    # Lưu ý: lệnh `make install` có thể thất bại khi cài đặt dịch vụ systemd, nhưng điều đó không sao.
    # Chúng ta sẽ tạo tệp dịch vụ theo cách thủ công sau.
    sudo make install 2>&1 | tee -a "$LOG_FILE" || true

    # What matters is that the binary was installed
    if ! command_exists shairport-sync; then
        cecho "red" "❌ Không tìm thấy tệp nhị phân Shairport-Sync sau khi cài đặt."
        cecho "yellow" "   Vị trí dự kiến: /usr/local/bin/shairport-sync"
        cecho "yellow" "   Quá trình 'make install' có thể đã thất bại - hãy kiểm tra nhật ký."
        exit 1
    fi

    cecho "green" "✓ Shairport-Sync đã được biên dịch và cài đặt."
    log "Lưu ý: Lệnh `make install` có thể hiển thị lỗi liên quan đến dịch vụ systemd - điều này là bình thường."
    echo

    #Cấu hình Shairport-Sync
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Cấu hình Shairport-Sync..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Tạo tệp cấu hình từ mẫu..."

    #Sao chép cấu hình mẫu làm cơ sở (được cài đặt bằng lệnh `make install`)
    if [ -f /etc/shairport-sync.conf.sample ]; then
        sudo cp /etc/shairport-sync.conf.sample /etc/shairport-sync.conf
        log "Đã sao chép cấu hình mẫu vào /etc/shairport-sync.conf"
    else
        cecho "yellow" "⚠ Không tìm thấy cấu hình mẫu, đang tạo cấu hình tối thiểu."
        #Sử dụng cấu hình tối thiểu nếu không có mẫu.
        sudo tee /etc/shairport-sync.conf > /dev/null <<'FALLBACK_EOF'
// Minimal configuration - sample file was not available
general = {};
alsa = {};
FALLBACK_EOF
    fi

    #Bây giờ hãy chỉnh sửa tệp cấu hình để thiết lập các giá trị của chúng ta bằng các lệnh sed đơn giản và đáng tin cậy.
    log "Cấu hình tên AirPlay: $airplay_name"

    #Đặt tên thiết bị AirPlay - bỏ dấu chú thích và đặt tên.
    sudo sed -i "s|^//[[:space:]]*name = .*|        name = \"$airplay_name\";|" /etc/shairport-sync.conf

    #Thiết lập thiết bị đầu ra - bỏ dấu chú thích và thiết lập nó.
    log "Cấu hình đầu ra âm thanh: $audio_device_plug"
    sudo sed -i "s|^[[:space:]]*output_device = .*|        output_device = \"$audio_device_plug\";|" /etc/shairport-sync.conf
    sudo sed -i "s|^//[[:space:]]*output_device = .*|        output_device = \"$audio_device_plug\";|" /etc/shairport-sync.conf

    #Đặt bộ điều khiển máy trộn âm lượng nếu có sẵn.
    if [ -n "$mixer_control" ]; then
        log "Cấu hình điều khiển bộ trộn âm thanh: $mixer_control on hw:$card_number"
        sudo sed -i "s|^//[[:space:]]*mixer_control_name = .*|        mixer_control_name = \"none\";|" /etc/shairport-sync.conf
        sudo sed -i "s|^[[:space:]]*mixer_control_name = .*|        mixer_control_name = \"none\";|" /etc/shairport-sync.conf
        #Ngoài ra, hãy thiết lập mixer_device nếu cần (thường được chú thích mặc định).
        sudo sed -i "s|^//[[:space:]]*mixer_device = .*|        mixer_device = \"default\";|" /etc/shairport-sync.conf
        sudo sed -i "s|^[[:space:]]*mixer_device = .*|        mixer_device = \"default\";|" /etc/shairport-sync.conf
    fi

	#general
	#sudo sed -i 's|^[[:space:]]*//[[:space:]]*ignore_volume_control = .*|        ignore_volume_control = "yes";|' /etc/shairport-sync.conf

	#MQTT Set Giá Trị
	sudo sed -i '/^mqtt[[:space:]]*=/,/^};/ {
		s|^[[:space:]]*//[[:space:]]*enabled = .*|        enabled = "yes";|
		s|^[[:space:]]*//[[:space:]]*hostname = .*|        hostname = "localhost";|
		s|^[[:space:]]*//[[:space:]]*port = .*|        port = 1883;|
		s|^[[:space:]]*//[[:space:]]*topic = .*|        topic = "shairport/vbot";|
		s|^[[:space:]]*//[[:space:]]*enable_remote = .*|        enable_remote = "yes";|
		s|^[[:space:]]*//[[:space:]]*publish_cover = .*|        publish_cover = "yes";|
	}' /etc/shairport-sync.conf

	#Metadata Set Giá trị
	sudo sed -i '/^metadata[[:space:]]*=/,/^};/ {
		s|^[[:space:]]*//[[:space:]]*enabled = .*|        enabled = "yes";|
		s|^[[:space:]]*//[[:space:]]*include_cover_art = .*|        include_cover_art = "yes";|
		s|^[[:space:]]*//[[:space:]]*cover_art_cache_directory = .*|        cover_art_cache_directory = "/tmp/shairport-sync/.cache/coverart";|
		s|^[[:space:]]*//[[:space:]]*pipe_name = .*|        pipe_name = "/tmp/shairport-sync-metadata";|
		s|^[[:space:]]*//[[:space:]]*pipe_timeout = .*|        pipe_timeout = 5000;|
	}' /etc/shairport-sync.conf

	#sessioncontrol
	sudo sed -i 's|^[[:space:]]*//[[:space:]]*run_this_before_entering_active_state = .*|        run_this_before_entering_active_state = "/home/pi/VBot_Offline/resource/airplay/vbot_airplay_play.sh";|' /etc/shairport-sync.conf
	sudo sed -i 's|^[[:space:]]*//[[:space:]]*run_this_after_exiting_active_state = .*|        run_this_after_exiting_active_state = "/home/pi/VBot_Offline/resource/airplay/vbot_airplay_play.sh";|' /etc/shairport-sync.conf
	sudo sed -i 's|^[[:space:]]*//[[:space:]]*active_state_timeout = .*|        active_state_timeout = 0.5;|' /etc/shairport-sync.conf
	sudo sed -i 's|^[[:space:]]*//[[:space:]]*run_this_before_play_begins = .*|        run_this_before_play_begins = "/home/pi/VBot_Offline/resource/airplay/vbot_airplay_play.sh";|' /etc/shairport-sync.conf
	sudo sed -i 's|^[[:space:]]*//[[:space:]]*run_this_after_play_ends = .*|        run_this_after_play_ends = "/home/pi/VBot_Offline/resource/airplay/vbot_airplay_stop.sh";|' /etc/shairport-sync.conf

    #Set output format
    sudo sed -i "s|^//[[:space:]]*output_rate = .*|        output_rate = \"auto\";|" /etc/shairport-sync.conf
    sudo sed -i "s|^[[:space:]]*output_rate = .*|        output_rate = \"auto\";|" /etc/shairport-sync.conf
    sudo sed -i "s|^//[[:space:]]*output_format = .*|        output_format = \"S16\";|" /etc/shairport-sync.conf
    sudo sed -i "s|^[[:space:]]*output_format = .*|        output_format = \"S16\";|" /etc/shairport-sync.conf

    #Set volume settings
    sudo sed -i "s|^//[[:space:]]*volume_max_db = .*|        volume_max_db = 4.0;|" /etc/shairport-sync.conf
    sudo sed -i "s|^//[[:space:]]*default_airplay_volume = .*|        default_airplay_volume = -6.0;|" /etc/shairport-sync.conf
    sudo sed -i "s|^//[[:space:]]*high_volume_idle_timeout_in_minutes = .*|        high_volume_idle_timeout_in_minutes = 1;|" /etc/shairport-sync.conf

    #Xác minh tệp cấu hình đã được tạo.
    if [ ! -f /etc/shairport-sync.conf ]; then
        cecho "red" "❌ Tệp cấu hình không được tạo."
        exit 1
    fi

    cecho "green" "✓ Tệp cấu hình đã được tạo và tùy chỉnh."

    #Nếu có thể, hãy đặt âm lượng bộ trộn ở mức tối đa.
    if [ -n "$mixer_control" ]; then
        cecho "blue" "Đặt âm lượng bộ trộn thành 100%..."
        if amixer -c "$card_number" set "$mixer_control" 100% unmute > /dev/null 2>&1; then
            sudo alsactl store > /dev/null 2>&1 || true
            cecho "green" "✓ Âm lượng bàn trộn được đặt ở mức tối đa."
        else
            cecho "yellow" "⚠ Không thể điều chỉnh âm lượng bộ trộn (có thể không được hỗ trợ)"
        fi
    fi
    echo

    #Tạo/Cập nhật dịch vụ Systemd
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Thiết lập dịch vụ tự khởi động..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Tạo người dùng và nhóm shairport-sync..."

    #Tạo người dùng và nhóm cho dịch vụ shairport-sync
    if ! getent group shairport-sync >/dev/null 2>&1; then
        sudo groupadd -r shairport-sync
        log "Đã tạo nhóm shairport-sync"
    fi

    if ! getent passwd shairport-sync >/dev/null 2>&1; then
        sudo useradd -r -M -g shairport-sync -s /usr/sbin/nologin -G audio shairport-sync
        log "Đã tạo người dùng shairport-sync"
    fi

    log "Tạo dịch vụ systemd theo cách thủ công (lệnh make install đôi khi thất bại ở bước này)..."

	# Tạo tệp dịch vụ systemd thủ công - cách này đáng tin cậy hơn so với lệnh 'make install'
	# lệnh này thường thất bại ở bước cài đặt dịch vụ systemd trên Raspberry Pi
    sudo tee /lib/systemd/system/shairport-sync.service > /dev/null <<EOF
[Unit]
Description=Shairport Sync - AirPlay Audio Receiver
After=sound.target network-online.target

[Service]
ExecStart=/usr/local/bin/shairport-sync
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable shairport-sync 2>&1 | tee -a "$LOG_FILE"
    sudo systemctl restart shairport-sync 2>&1 | tee -a "$LOG_FILE"
    sleep 5

    if ! check_service "shairport-sync" 5; then
        cecho "red" "❌ Dịch vụ Shairport-Sync không khởi động được."
        cecho "yellow" "Kiểm tra trạng thái dịch vụ..."
        sudo systemctl status shairport-sync --no-pager -l | tail -20
        exit 1
    fi

    #Kiểm tra xem avahi-daemon có đang chạy hay không (bắt buộc để phát hiện thiết bị qua AirPlay).
    cecho "blue" "Kiểm tra tiến trình nền Avahi (cần thiết cho việc phát hiện thiết bị)..."
    if ! systemctl is-active --quiet avahi-daemon; then
        cecho "yellow" "⚠ Trình nền Avahi không chạy, đang cố gắng khởi động...."
        sudo systemctl start avahi-daemon
        sleep 2
        if systemctl is-active --quiet avahi-daemon; then
            cecho "green" "✓ Trình nền Avahi đã khởi động"
        else
            cecho "red" "❌ Trình nền Avahi không khởi động được - Thiết bị AirPlay có thể không được phát hiện."
        fi
    else
        cecho "green" "✓ Trình nền Avahi đang chạy"
    fi
    echo

    #Hướng dẫn quản lý nguồn Wi-Fi
    if [ "$disable_wifi_pm" = true ]; then
        cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        cecho "blue" "   Quản lý nguồn Wi-Fi"
        cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        log "Người dùng yêu cầu tắt tính năng quản lý nguồn Wi-Fi."

        cecho "yellow" "📝 Cần cấu hình thủ công quản lý nguồn Wi-Fi.:"
        echo
        cecho "blue" "Sau khi quá trình cài đặt hoàn tất, hãy tắt chế độ tiết kiệm điện Wi-Fi để tránh"
        cecho "blue" "Âm thanh bị gián đoạn. Bạn có hai lựa chọn.:"
        echo
        cecho "green" "Phương án 1: Sử dụng raspi-config (Khuyến nghị)"
        cecho "blue" "  1. Chạy lệnh: sudo raspi-config"
        cecho "blue" "  2. Đi tới: Performance Options → Wireless LAN → Power Management"
        cecho "blue" "  3. Chọn: Disable"
        echo
        cecho "green" "Tùy chọn 2: Lệnh thủ công"
        cecho "blue" "  Chạy lệnh sau: sudo iw dev wlan0 set power_save off"
        cecho "blue" "  (Lưu ý: Chức năng này chỉ tạm thời, reboot khởi động lại hệ thống để được áp dụng)"
        echo
        cecho "yellow" "⚠ Chúng tôi không thực hiện việc này tự động để tránh ngắt kết nối phiên SSH của bạn."
        log "Hướng dẫn quản lý nguồn Wi-Fi được cung cấp cho người dùng."
        echo
    fi

    #Cấu hình tường lửa (nếu đang hoạt động)
    if command_exists ufw && sudo ufw status | grep -q "Status: active"; then
        cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        cecho "blue" "   Cấu hình tường lửa..."
        cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

        #Cho phép mDNS để phát hiện AirPlay.
        sudo ufw allow 5353/udp comment 'mDNS for AirPlay' 2>&1 | tee -a "$LOG_FILE"
        #Cho phép các cổng NQPTP
        sudo ufw allow 319/udp comment 'NQPTP PTP' 2>&1 | tee -a "$LOG_FILE"
        sudo ufw allow 320/udp comment 'NQPTP PTP' 2>&1 | tee -a "$LOG_FILE"
        #Cổng AirPlay
        sudo ufw allow 7000/tcp comment 'AirPlay' 2>&1 | tee -a "$LOG_FILE"

        cecho "green" "✓ Đã thêm quy tắc tường lửa"
        echo
    fi

    #Kiểm tra âm thanh
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    cecho "blue" "   Kiểm tra đầu ra âm thanh..."
    cecho "blue" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo

    read -p "Bạn có muốn kiểm tra đầu ra âm thanh không?? (Y/n): " test_audio || true
    if [[ -z "$test_audio" || "$test_audio" =~ ^[Yy]$ ]]; then
        cecho "yellow" "Phát âm thanh thử nghiệm sau 2 giây..."
        cecho "yellow" "(Bạn sẽ nghe thấy một giọng nói nói rằng: 'Front Left', 'Front Right')"
        sleep 2

        if timeout 10 speaker-test -D "$audio_device_plug" -c 2 -t wav -l 1 > /dev/null 2>&1; then
            echo
            cecho "green" "✓ Đã hoàn tất kiểm tra âm thanh!"
            read -p "Bạn có nghe thấy âm thanh thử nghiệm không? (y/N): " heard_sound || true
            if [[ ! "$heard_sound" =~ ^[Yy]$ ]]; then
                cecho "yellow" "⚠ Nếu bạn không nghe thấy âm thanh, hãy kiểm tra:"
                cecho "yellow" "  - Kết nối loa/tai nghe"
                cecho "yellow" "  - Mức âm lượng trên amply/loa của bạn"
                cecho "yellow" "  - Nguồn DAC USB"
                cecho "yellow" "  - Hoặc cấu hình được thiết lập riêng cho VBot Assistant nên khi test âm thanh sẽ không có âm thanh phát ra"
            fi
        else
            cecho "yellow" "⚠ Không thể chạy kiểm tra âm thanh, nhưng quá trình thiết lập đã hoàn tất.."
            cecho "yellow" "  Bạn có thể kiểm tra thủ công bằng: speaker-test -D $audio_device_plug -c 2 -t wav"
        fi
    fi
    echo

    #Dọn dẹp
    cecho "blue" "Dọn dẹp các tệp tạm thời..."
    rm -rf /tmp/nqptp /tmp/shairport-sync 2>/dev/null || true
    rm -f "${LOG_FILE}.make" 2>/dev/null || true
    cecho "green" "✓ Dọn dẹp hoàn tất"
    echo

    INSTALLATION_FAILED=0  # Cài đặt thành công

    #Thông báo thành công
    cecho "green" "╔═════════════════════════════════════════════════════╗"
    cecho "green" "║                                                     ║"
    cecho "green" "║            ✅ QUÁ TRÌNH CÀI ĐẶT HOÀN TẤT! ✅            ║"
    cecho "green" "║                                                     ║"
    cecho "green" "╚═════════════════════════════════════════════════════╝"
    echo
    log "=== Quá trình cài đặt đã hoàn tất thành công. ==="

    cecho "magenta" "🎵 Thiết bị AirPlay 2 của bạn đã sẵn sàng!"
    echo
    cecho "yellow" "  📱 Tên thiết bị:  $airplay_name"
    cecho "yellow" "  🔊 Đầu ra âm thanh: $audio_device_plug"
    cecho "yellow" "  🎚️  Âm lượng:       ${mixer_control:-Fixed (no hardware control)}"
    echo
    cecho "blue" "┌─────────────────────────────────────────────────────┐"
    cecho "blue" "│ Cách sử dụng:                                         │"
    cecho "blue" "│ 1. Mở ứng dụng Music/Spotify/YouTube trên iPhone/iPad của bạn. │"
    cecho "blue" "│ 2. Chạm vào biểu tượng AirPlay (📡)                       │"
    cecho "blue" "│ 3. Lựa chọn '$airplay_name'                     │"
    cecho "blue" "│ 4. Tận hưởng âm thanh không dây chất lượng cao!              │"
    cecho "blue" "└─────────────────────────────────────────────────────┘"
    echo
    cecho "yellow" "💡 Mẹo:"
    cecho "yellow" "   • Thiết bị sẽ xuất hiện trong vòng 30-60 giây sau khi khởi động lại."
    cecho "yellow" "   • Hãy đảm bảo iPhone và Raspberry Pi cùng kết nối với một mạng Wi-Fi."
    cecho "yellow" "   • Để có chất lượng tốt nhất, hãy sử dụng nguồn âm thanh không nén."
    if [ "$disable_wifi_pm" = true ]; then
        echo
        cecho "yellow" "📝 QUAN TRỌNG - Sau khi khởi động lại:"
        cecho "yellow" "   Đừng quên tắt chế độ quản lý nguồn Wi-Fi bằng lệnh raspi-config.!"
        cecho "yellow" "   Điều này giúp ngăn ngừa hiện tượng mất tiếng và gián đoạn âm thanh.."
    fi
    echo
    cecho "blue" "📋 Các lệnh hữu ích:"
    cecho "blue" "   Xem nhật ký trực tiếp:    sudo journalctl -u shairport-sync -f"
    cecho "blue" "   Khởi động lại dịch vụ:   sudo systemctl restart shairport-sync"
    cecho "blue" "   Kiểm tra trạng thái:      sudo systemctl status shairport-sync"
    cecho "blue" "   Chỉnh sửa cấu hình:       sudo nano /etc/shairport-sync.conf"
    cecho "blue" "   nhật ký cài đặt:  $LOG_FILE"
    echo

    read -p "Nhấn Enter để khởi động lại ngay (khuyến nghị), hoặc Ctrl+C để khởi động lại sau..." || {
        echo
        cecho "yellow" "Quá trình khởi động lại đã bị hủy bỏ. Hãy nhớ khởi động lại sau bằng lệnh: sudo reboot"
        exit 0
    }

    log "Khởi động lại do người dùng thực hiện"
    cecho "yellow" "Khởi động lại sau 3 giây..."
    sleep 3
    sudo reboot
}

#Chạy
main "$@"
