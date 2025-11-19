<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

$data = array(
    "info" => array(
        "author_name" => "Vũ Tuyển",
        "description" => "Trợ Lý Ảo, Loa Thông Minh Tiếng Việt VBot Assistant",
        "github" => "https://github.com/marion001/VBot_Offline.git",
        "project_name" => "VBot Offline"
    ),
    "success" => true,
    "user_name" => $Config['contact_info']['full_name'],
    "ip_address" => $Domain,
    "port_api" => $Config['api']['port'],
    "host_name" => $HostName,
);
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
