<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
$data = array(
    "info" => array(
        "author_name" => "Vũ Tuyển",
        "description" => "Trợ Lý Ảo, Loa Thông Minh Tiếng Việt VBot Assistant",
        "github" => "https://github.com/marion001/VBot_Offline.git",
        "project_name" => "VBot Offline"
    ),
    "success" => true,
    "user_name" => $Config['contact_info']['full_name'],
    #"vbot_program_path" => $VBot_Offline,
    #"vbot_interface_path" => $HTML_VBot_Offline,
    "ip_address" => $Domain,
    "port_api" => $Config['api']['port'],
    "host_name" => $HostName,
);

// Thiết lập header cho JSON
header('Content-Type: application/json');

// Chuyển dữ liệu thành JSON và xuất
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
