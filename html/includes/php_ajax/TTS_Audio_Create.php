<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

include '../../Configuration.php';
//Cấu hình tiêu đề CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']) {
	session_start();
	// Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
	if (
		!isset($_SESSION['user_login']) ||
		(isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
	) {
		// Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
		session_unset();
		session_destroy();
		echo json_encode([
			'success' => false,
			'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}

//Chuyển đổi có  dấu thành không dấu
function removeVietnameseAccents($str)
{
	$unicode = [
		'a' => ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ'],
		'd' => ['đ'],
		'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ'],
		'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị'],
		'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ'],
		'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự'],
		'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
		'A' => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ'],
		'D' => ['Đ'],
		'E' => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ'],
		'I' => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị'],
		'O' => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ'],
		'U' => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự'],
		'Y' => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ'],
	];
	foreach ($unicode as $nonUnicode => $uni) {
		$str = str_replace($uni, $nonUnicode, $str);
	}
	return $str;
}

//chuyển đổi tên file
function sanitize_filename($text)
{
	$text = strtolower($text);
	$text = removeVietnameseAccents($text);
	$text = preg_replace('/[^a-z0-9\s]/', '', $text);
	$text = preg_replace('/\s+/', '_', trim($text));
	return 'create_audio_' . $text . '.mp3';
}
?>
<?php
if (isset($_GET['create_tts_audio'])) {
	$required_params = ['source_tts', 'text'];
	foreach ($required_params as $param) {
		if (empty($_GET[$param])) {
			echo json_encode(['success' => false, 'error' => "Thiếu tham số: $param"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}
	}
	$source = $_GET['source_tts'];
	$text = trim($_GET['text']);

	if ($source === 'tts_ggcloud') {
		// Kiểm tra thêm thông tin đầu vào
		$required_params = ['language_code', 'voice_name', 'speaking_rate'];
		foreach ($required_params as $param) {
			if (empty($_GET[$param])) {
				echo json_encode(['success' => false, 'error' => "Thiếu tham số: $param"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				exit;
			}
		}

		$lang = $_GET['language_code'];
		$voice_name = $_GET['voice_name'];
		$speakingRate = $_GET['speaking_rate'];
		$filename = sanitize_filename($text);
		$jsonKeyPath = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file'];
		$extraSavePath = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'];
		if (!file_exists($extraSavePath)) {
			mkdir($extraSavePath, 0777, true);
		}

		// Hàm lấy Access Token
		function getAccessTokenFromJson($jsonKeyPath)
		{
			$key = json_decode(file_get_contents($jsonKeyPath), true);
			$header = base64_encode(json_encode([
				'alg' => 'RS256',
				'typ' => 'JWT',
				'kid' => $key['private_key_id']
			]));
			$iat = time();
			$exp = $iat + 3600;
			$claimSet = base64_encode(json_encode([
				'iss' => $key['client_email'],
				'scope' => 'https://www.googleapis.com/auth/cloud-platform',
				'aud' => 'https://oauth2.googleapis.com/token',
				'iat' => $iat,
				'exp' => $exp
			]));
			$signatureInput = $header . '.' . $claimSet;
			openssl_sign($signatureInput, $signature, $key['private_key'], 'sha256WithRSAEncryption');
			$jwt = $signatureInput . '.' . base64_encode($signature);
			$postFields = http_build_query([
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion' => $jwt
			]);
			$ch = curl_init('https://oauth2.googleapis.com/token');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
			$response = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($response, true);
			return $result['access_token'] ?? null;
		}
		// Hàm gọi API Text-to-Speech
		function synthesizeText($text, $accessToken, $outputFile, $lang, $voice_name, $speakingRate)
		{
			$data = [
				'input' => ['text' => $text],
				'voice' => [
					'languageCode' => $lang,
					'name' => $voice_name
				],
				'audioConfig' => [
					'audioEncoding' => 'MP3',
					'speakingRate' => $speakingRate
				]
			];
			$ch = curl_init('https://texttospeech.googleapis.com/v1/text:synthesize');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: Bearer ' . $accessToken,
				'Content-Type: application/json'
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			$response = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($response, true);
			if (isset($result['audioContent'])) {
				$audioData = base64_decode($result['audioContent']);
				file_put_contents($outputFile, $audioData);
				return true;
			}
			return false;
		}
		// Xử lý chính
		$accessToken = getAccessTokenFromJson($jsonKeyPath);
		$filename = sanitize_filename($text);
		$outputFile = $extraSavePath . '/' . $filename;
		$response = ['success' => false, 'message' => 'Không tạo được file âm thanh.'];
		if ($accessToken) {
			$success = synthesizeText($text, $accessToken, $outputFile, $lang, $voice_name, $speakingRate);
			if ($success) {
				$response = [
					'success' => true,
					'message' => 'Tạo file âm thanh thành công',
					'file_name' => $filename,
					'file_path' => $outputFile
				];
			}
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	} else if ($source === 'tts_zalo') {
		// Kiểm tra thêm thông tin đầu vào
		$required_params = ['encode_type', 'speaker_speed', 'speaker_id'];
		foreach ($required_params as $param) {
			if (empty($_GET[$param])) {
				echo json_encode([
					'success' => false,
					'error' => "Thiếu tham số: $param"
				], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				exit;
			}
		}
		$apiKeys = $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key'];
		$success = false;
		foreach ($apiKeys as $apiKey) {
			$ch = curl_init();
			$postFields = http_build_query([
				'input' => $text,
				'speaker_id' => $_GET['speaker_id'],
				'speed' => $_GET['speaker_speed'],
				'encode_type' => $_GET['encode_type']
			]);
			curl_setopt_array($ch, [
				CURLOPT_URL => 'https://api.zalo.ai/v1/tts/synthesize',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postFields,
				CURLOPT_HTTPHEADER => [
					"apikey: $apiKey",
					"Content-Type: application/x-www-form-urlencoded"
				]
			]);
			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpCode === 200) {
				$result = json_decode($response, true);
				if (isset($result['error_code']) && $result['error_code'] == 0) {
					$audioUrl = $result['data']['url'];
					$filename = sanitize_filename($text);
					$savePath = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'] . '/' . $filename;
					$audioData = file_get_contents($audioUrl);
					if ($audioData !== false) {
						file_put_contents($savePath, $audioData);
						echo json_encode([
							'success' => true,
							'message' => 'Tạo file âm thanh thành công',
							'file_path' => $savePath,
							'file_name' => $filename,
							//'api_key' => $apiKey,
							'url' => $audioUrl
						], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
					} else {
						echo json_encode([
							'success' => false,
							'error' => 'Không thể tải file từ URL TTS.'
						], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
					}
					$success = true;
					break;
				}
			}
		}
		if (!$success) {
			echo json_encode([
				'success' => false,
				'error' => 'Tất cả API key TTS Zalo đều thất bại.'
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
	}
	exit();
}
?>