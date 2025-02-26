<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  include '../../Configuration.php';
  header('Content-Type: application/json');
  
  //Tets Code Yaml Hass
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yaml_test_control_homeassistant'])) {
      $actionData = json_decode($_POST['yaml_test_control_homeassistant'], true);
      if (!$actionData || empty($actionData['action']) || empty($actionData['target']['entity_id'])) {
          echo json_encode(['success' => false, 'message' => 'Thiếu "action" hoặc "entity_id" trong dữ liệu']);
          exit;
      }
      $action = $actionData['action'];
      $data = isset($actionData['data']) ? $actionData['data'] : [];
      $target = $actionData['target'];
  	$entity_id = $target['entity_id'];
      list($domain, $service) = explode('.', $action);
      if (is_string($entity_id)) {
  		#Nếu là chuỗi
          $payload = ['entity_id' => [$entity_id]];
      } elseif (is_array($entity_id)) {
          //Nếu là Mảng
          $payload = ['entity_id' => $entity_id];
      } else {
          // Còn lại gắn mảng rỗng
          $payload = ['entity_id' => []];
      }
      $headers = [
          "Authorization: Bearer ".$Config['home_assistant']['long_token'],
          "Content-Type: application/json"
      ];
  	function sendRequest($url, $headers, $payload) {
  		$ch = curl_init($url);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  		curl_setopt($ch, CURLOPT_POST, true);
  		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  		$response = curl_exec($ch);
  		if ($response === false) {
  			$error = curl_error($ch);
  			curl_close($ch);
  			return ['success' => false, 'message' => $error];
  		}
  		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  		curl_close($ch);
  		switch ($statusCode) {
  			case 200:
  				return ['success' => true, 'message' => 'Thao tác thành công'];
  			case 400:
  				return ['success' => false, 'message' => 'Lỗi: 400 - Yêu cầu không hợp lệ'];
  			case 401:
  				return ['success' => false, 'message' => 'Lỗi: 401 - Không được phép'];
  			case 404:
  				return ['success' => false, 'message' => 'Lỗi: 404 - Không tìm thấy'];
  			case 405:
  				return ['success' => false, 'message' => 'Lỗi: 405 - Phương pháp không được phép'];
  			default:
  				return ['success' => false, 'message' => 'Lỗi: ' . $statusCode];
  		}
  	}
  
      //Chạy URL Nội Bộ
      $response = sendRequest($Config['home_assistant']['internal_url'].'/api/services/' . $domain . '/'.$service, $headers, $payload);
      //Chạy URL Ngoài Nếu Lỗi
      if (!$response['success']) {
          $response = sendRequest($Config['home_assistant']['external_url'].'/api/services/'.$domain.'/'.$service, $headers, $payload);
      }
      echo json_encode($response);
      exit;
  }
  
  
  #kiểm tra kết nối tới SSH Server
  if (isset($_GET['check_ssh'])) {
      $ssh_host = $_GET['host'];
      $ssh_port = $_GET['port'];
      $ssh_user = $_GET['user'];
      $ssh_pass = $_GET['pass'];
      $response = [
          'success' => false,
          'message' => '',
      ];
      if (empty($ssh_host) || empty($ssh_user) || empty($ssh_pass) || empty($ssh_port)) {
          $response['message'] = 'Vui lòng cung cấp đầy đủ ssh_host, ssh_port, ssh_user và ssh_pass.';
          echo json_encode($response);
          exit();
      }
      if (!function_exists('ssh2_connect')) {
          $response['message'] = 'Tiện ích mở rộng PHP SSH2 chưa được cài đặt: sudo apt-get install php-ssh2';
          echo json_encode($response);
          exit();
      }
      $connection = @ssh2_connect($ssh_host, $ssh_port);
      if (!$connection) {
          $response['message'] = 'Không thể kết nối tới máy chủ SSH, Kiểm tra lại địa chỉ máy chủ hoặc port, hoặc SSH chưa được kích hoạt trên máy chủ';
          echo json_encode($response);
          exit();
      }
      if (!ssh2_auth_password($connection, $ssh_user, $ssh_pass)) {
          $response['message'] = 'Xác thực SSH thất bại, Kiểm tra lại Tên Đăng Nhập hoặc Mật Khẩu';
          echo json_encode($response);
          exit();
      }
      $response['success'] = true;
      $response['message'] = 'Kết nối SSH thành công!';
      echo json_encode($response);
      ssh2_disconnect($connection);
  	gc_collect_cycles();
      exit();
  }
  
  if (isset($_GET['VBot_CMD'])) {
      $Command = $_GET['Command'] ?? '';
      if (empty($Command)) {
              echo json_encode([
                  'success' => false,
                  'message' => 'Không có dữ liệu câu lệnh đầu vào',
                  'data' => null
              ]);
              exit();
      }
  	$Command_decode = base64_decode($Command);
      $connection = ssh2_connect($ssh_host, $ssh_port);
      $result = [
          'success' => false,
          'message' => '',
          'data' => null,
      ];
      if ($connection) {
          if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
              $stream = ssh2_exec($connection, $Command_decode);
              if ($stream) {
                  stream_set_blocking($stream, true);
                  $output = stream_get_contents($stream);
                  $result['success'] = true;
                  $result['message'] = 'Lệnh: "' . $Command_decode . '" đã được thực thi.';
                  $result['data'] = $output;
              } else {
                  $result['message'] = 'Không thể thực thi lệnh trên SSH.';
              }
          } else {
              $result['message'] = 'Xác thực SSH không thành công.';
          }
      } else {
          $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
      }
      echo json_encode($result);
      exit();
  }
  
  if (isset($_GET['start_vbot_service'])) {
      $CMD = "systemctl --user start VBot_Offline.service";
      $connection = ssh2_connect($ssh_host, $ssh_port);
      $result = [
          'success' => false,
          'message' => ''
      ];
      if ($connection) {
          if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
              $stream = ssh2_exec($connection, $CMD);
              stream_set_blocking($stream, true);
              $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
                  $result['success'] = true;
                  $result['message'] = 'Dịch vụ VBot đã được khởi chạy thành công.';
          } else {
              $result['message'] = 'Xác thực SSH không thành công.';
          }
      } else {
          $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
      }
      echo json_encode($result);
      exit; 
  }
  
  if (isset($_GET['stop_vbot_service'])) {
      $CMD = "systemctl --user stop VBot_Offline.service";
      $connection = ssh2_connect($ssh_host, $ssh_port);
      $result = [
          'success' => false,
          'message' => ''
      ];
      if ($connection) {
          if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
              $stream = ssh2_exec($connection, $CMD);
              stream_set_blocking($stream, true);
              $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
                  $result['success'] = true;
                  $result['message'] = 'Dịch vụ VBot đã được dừng thành công.';
          } else {
              $result['message'] = 'Xác thực SSH không thành công.';
          }
      } else {
          $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
      }
      echo json_encode($result);
      exit; 
  }
  
  if (isset($_GET['restart_vbot_service'])) {
      $CMD = "systemctl --user restart VBot_Offline.service";
      $connection = ssh2_connect($ssh_host, $ssh_port);
      $result = [
          'success' => false,
          'message' => ''
      ];
      if ($connection) {
          if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
              $stream = ssh2_exec($connection, $CMD);
              stream_set_blocking($stream, true);
              $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
                  $result['success'] = true;
                  $result['message'] = 'Dịch vụ VBot đã được khởi động lại thành công.';
          } else {
              $result['message'] = 'Xác thực SSH không thành công.';
          }
      } else {
          $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
      }
      echo json_encode($result);
      exit; 
  }
  
  if (isset($_GET['reboot_os'])) {
      $CMD = "sudo reboot";
      $connection = ssh2_connect($ssh_host, $ssh_port);
      $result = [
          'success' => false,
          'message' => ''
      ];
      if ($connection) {
          if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
              $stream = ssh2_exec($connection, $CMD);
              stream_set_blocking($stream, true);
              $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
                  $result['success'] = true;
                  $result['message'] = 'Đang khởi động lại toàn bộ hệ thống';
          } else {
              $result['message'] = 'Xác thực SSH không thành công.';
          }
      } else {
          $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
      }
      echo json_encode($result);
      exit; 
  }
  
  if (isset($_GET['check_hass']))
  {
      $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
      $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';
      if (!empty($url))
      {
          $ch = curl_init($url . '/api/config');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
          $response = curl_exec($ch);
          if (curl_errno($ch))
          {
              $curlError = curl_error($ch);
              if (strpos($curlError, 'Failed to connect') !== false)
              {
                  $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
              }
              else
              {
                  $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
              }
              echo json_encode(['success' => false, 'message' => $message]);
          }
          else
          {
              $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
              if ($httpCode >= 200 && $httpCode < 300)
              {
                  echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response) ]);
              }
              else if ($httpCode = 401 && $httpCode = 200)
              {
                  echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response) ]);
              }
              else
              {
                  echo json_encode(['success' => false, 'response' => json_decode($response) , 'message' => 'HTTP Error: ' . $httpCode]);
              }
          }
          curl_close($ch);
      }
      else
      {
          echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
      }
      exit();
  }
  
  
  if (isset($_GET['get_hass_all']))
  {
      $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
      $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';
      if (!empty($url))
      {
          $ch = curl_init($url . '/api/states');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
          $response = curl_exec($ch);
          if (curl_errno($ch))
          {
              $curlError = curl_error($ch);
              if (strpos($curlError, 'Failed to connect') !== false)
              {
                  $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
              }
              else
              {
                  $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
              }
              echo json_encode(['success' => false, 'message' => $message]);
          }
          else
          {
              $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
              if ($httpCode >= 200 && $httpCode < 300)
              {
                  echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response) ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  				$filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
  		if (!file_exists($filePath_HASS)) {
  			file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  			chmod($filePath_HASS, 0777);
  		}
  				$existingData['get_hass_all'] = json_decode($response);
  				$jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  				file_put_contents($filePath_HASS, $jsonData);
  			}
              else if ($httpCode = 401 && $httpCode = 200)
              {
                  echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response) ]);
              }
              else
              {
                  echo json_encode(['success' => false, 'response' => json_decode($response) , 'message' => 'HTTP Error: ' . $httpCode]);
              }
          }
          curl_close($ch);
      }
      else
      {
          echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
      }
      exit();
  }
  
  
  if (isset($_GET['del_get_hass_all'])) {
  	$response = [
  		'success' => false,
  		'message' => 'Đã có lỗi xảy ra.'
  	];
      $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
      if (!file_exists($filePath_HASS)) {
          file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT));
          chmod($filePath_HASS, 0777);
      }
      $existingData = json_decode(file_get_contents($filePath_HASS), true);
      if ($existingData === null) {
          $existingData = [];
      }
      $existingData['get_hass_all'] = [];
      $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      if ($jsonData !== false) {
          if (file_put_contents($filePath_HASS, $jsonData)) {
              $response['success'] = true;
              $response['message'] = 'Dữ Liệu Đồng Bộ trước đó đã được xóa thành công.';
          } else {
              $response['message'] = 'Lỗi: Không thể lưu dữ liệu rỗng vào file.';
          }
      } else {
          $response['message'] = 'Lỗi: Không thể chuyển đổi dữ liệu thành JSON.';
      }
  echo json_encode($response);
  }
  
  if (isset($_GET['check_key_picovoice'])) {
      $key =  str_replace(' ', '+', @$_GET['key']);
      $lang = $VBot_Offline.'resource/hotword/'.@$_GET['lang'];
      $response = [
          'success' => false,
          'message' => '',
      ];
      if (empty($_GET['lang']) || empty($_GET['key'])) {
          $response['message'] = 'Vui lòng cung cấp đầy đủ key, lang';
          echo json_encode($response);
          exit();
      }
  	$modelFilePath = $VBot_Offline.'resource/picovoice/library/'.$Config['smart_config']['smart_wakeup']['hotword']['library'][$_GET['lang']]['modelFilePath'];
      $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Check_Key_Picovoice.py $key $lang $modelFilePath");
      $connection = ssh2_connect($ssh_host, $ssh_port);
      if (!$connection) {
          $response['message'] = 'Không thể kết nối tới máy chủ SSH';
          echo json_encode($response);
          exit();
      }
      if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
          $response['message'] = 'Xác thực SSH không thành công.';
          echo json_encode($response);
          exit();
      }
      $stream = ssh2_exec($connection, $CMD);
      if (!$stream) {
          $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
          echo json_encode($response);
          exit();
      }
      stream_set_blocking($stream, true);
      $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      $output = stream_get_contents($stream_out);
      echo $output;
  	exit();
  }
  
  if (isset($_GET['check_mqtt'])) {
      if (isset($_GET['host'], $_GET['port'], $_GET['user'], $_GET['pass'])) {
          require('./phpMQTT.php');
          $server = $_GET['host'];
          $port = $_GET['port'];
          $username = $_GET['user'];
          $password = $_GET['pass'];
          $client_id = 'VBot_TEST_CONNECT_MQTT_client_' . uniqid();
          $mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);
          if ($mqtt->connect(true, NULL, $username, $password)) {
              $response = [
                  'success' => true,
                  'message' => 'Kết nối tới máy chủ MQTT thành công: '.$server.':'.$port
              ];
              $mqtt->close();
          } else {
              $response = [
                  'success' => false,
                  'message' => 'Không thể kết nối tới máy chủ MQTT: '.$server.':'.$port.' hãy kiểm tra lại Cổng Port, Tài Khoản,, Mật Khẩu'
              ];
          }
      } else {
          $response = [
              'success' => false,
              'message' => 'Thiếu thông tin kết nối MQTT, cần nhập đủ thông tin: Máy Chủ MQTT, Cổng PORT, Tài Khoản, Mật Khẩu, Hoặc máy chủ MQTT có lỗi, không hoạt động'
          ];
      }
      echo json_encode($response);
      exit();
  }
  
  if (isset($_GET['Picovoice_Version'])) {
      $url = 'https://pypi.org/rss/project/picovoice/releases.xml';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($response === false || $http_code !== 200) {
          header('Content-Type: application/json');
          echo json_encode([
              'success' => false,
              'message' => 'Không thể kết nối tới RSS feed.',
              'error' => curl_error($ch)
          ]);
      } else {
          header('Access-Control-Allow-Origin: *');
          header('Content-Type: application/xml');
          echo $response;
      }
      curl_close($ch);
      exit();
  }
  ?>