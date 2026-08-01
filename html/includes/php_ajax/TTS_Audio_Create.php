<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
	session_start();
	if (
		!isset($_SESSION['user_login']) ||
		(isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
	) {
		session_unset();
		session_destroy();
		vbotApiJsonResponse([
			'success' => false,
			'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
		], 401);
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
	$text = substr($text, 0, 100);
	if ($text === '') {
		$text = bin2hex(random_bytes(6));
	}
	return 'create_audio_' . $text . '.mp3';
}

function downloadTtsAudio($url)
{
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return false;
	}
	$parts = parse_url($url);
	if (!isset($parts['scheme'], $parts['host']) || strtolower($parts['scheme']) !== 'https') {
		return false;
	}
	$resolvedIp = gethostbyname($parts['host']);
	if (!filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
		return false;
	}
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
	curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	$data = curl_exec($ch);
	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($data === false || $statusCode < 200 || $statusCode >= 300 || strlen($data) > 10485760) {
		return false;
	}
	return $data;
}

if (isset($_POST['create_tts_audio'])) {
	vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
	$required_params = ['source_tts', 'text'];
	foreach ($required_params as $param) {
		if (empty($_POST[$param])) {
			vbotApiJsonResponse(['success' => false, 'error' => "Thiếu tham số: $param"], 400);
		}
	}
	$source = $_POST['source_tts'];
	$text = trim($_POST['text']);
	if (!in_array($source, ['tts_ggcloud', 'tts_zalo', 'tts_edge'], true) || mb_strlen($text) > 5000) {
		vbotApiJsonResponse(['success' => false, 'error' => 'Nguồn TTS không hợp lệ hoặc nội dung vượt quá 5000 ký tự'], 400);
	}
	$ttsSaveDirectory = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'];
	if (!is_dir($ttsSaveDirectory) && !@mkdir($ttsSaveDirectory, 0777, true)) {
		vbotApiJsonResponse(['success' => false, 'error' => 'Không thể tạo thư mục lưu âm thanh TTS'], 500);
	}
	@chmod($ttsSaveDirectory, 0777);
	if ($source === 'tts_ggcloud') {
		$required_params = ['language_code', 'voice_name', 'speaking_rate'];
		foreach ($required_params as $param) {
			if (empty($_POST[$param])) {
				vbotApiJsonResponse(['success' => false, 'error' => "Thiếu tham số: $param"], 400);
			}
		}
		$lang = trim($_POST['language_code']);
		$voice_name = trim($_POST['voice_name']);
		$speakingRate = (float) $_POST['speaking_rate'];
		if (!preg_match('/^[a-z]{2,3}-[A-Z]{2}$/', $lang) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $voice_name) || $speakingRate < 0.25 || $speakingRate > 4.0) {
			vbotApiJsonResponse(['success' => false, 'error' => 'Thông số giọng Google TTS không hợp lệ'], 400);
		}
		$filename = sanitize_filename($text);
		$jsonKeyPath = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file'];
		$extraSavePath = $ttsSaveDirectory;
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
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$response = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($response, true);
			if (isset($result['audioContent'])) {
				$audioData = base64_decode($result['audioContent']);
				file_put_contents($outputFile, $audioData, LOCK_EX);
				@chmod($outputFile, 0777);
				return true;
			}
			return false;
		}
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
		vbotApiJsonResponse($response, !empty($response['success']) ? 200 : 502);
	} else if ($source === 'tts_edge') {
		$voiceName = trim(isset($_POST['voice_name']) ? $_POST['voice_name'] : '');
		$speakingRate = (float) (isset($_POST['speaking_rate']) ? $_POST['speaking_rate'] : 1);
		if (!preg_match('/^[a-z]{2,3}-[A-Z]{2}-[A-Za-z0-9]+Neural$/', $voiceName) || $speakingRate < 0.5 || $speakingRate > 2.0) {
			vbotApiJsonResponse(['success' => false, 'error' => 'Thông số giọng Edge TTS không hợp lệ'], 400);
		}
		$ratePercent = (int) round(($speakingRate - 1) * 100);
		$edgeRate = ($ratePercent >= 0 ? '+' : '') . $ratePercent . '%';
		$filename = sanitize_filename($text);
		$outputFile = $ttsSaveDirectory . '/' . $filename;
		$command = 'python3 -m edge_tts'
			. ' --voice ' . escapeshellarg($voiceName)
			. ' --rate=' . escapeshellarg($edgeRate)
			. ' --text ' . escapeshellarg($text)
			. ' --write-media ' . escapeshellarg($outputFile);
		$connection = @ssh2_connect($ssh_host, $ssh_port);
		if (!$connection || !@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
			vbotApiJsonResponse(['success' => false, 'error' => 'Không thể kết nối SSH để chạy Edge TTS'], 500);
		}
		$stream = @ssh2_exec($connection, $command);
		if (!$stream) {
			vbotApiJsonResponse(['success' => false, 'error' => 'Không thể khởi chạy Edge TTS'], 500);
		}
		stream_set_blocking($stream, true);
		$stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
		stream_get_contents($stream);
		$errorOutput = trim(stream_get_contents($stderrStream));
		fclose($stream);
		if (!is_file($outputFile) || filesize($outputFile) <= 0) {
			vbotApiJsonResponse([
				'success' => false,
				'error' => 'Edge TTS không tạo được file âm thanh' . ($errorOutput !== '' ? ': ' . substr($errorOutput, 0, 300) : '')
			], 500);
		}
		@chmod($outputFile, 0777);
		vbotApiJsonResponse([
			'success' => true,
			'message' => 'Tạo file âm thanh Edge TTS thành công',
			'file_name' => $filename,
			'file_path' => $outputFile
		]);
	} else if ($source === 'tts_zalo') {
		$required_params = ['encode_type', 'speaker_speed', 'speaker_id'];
		foreach ($required_params as $param) {
			if (empty($_POST[$param])) {
				vbotApiJsonResponse([
					'success' => false,
					'error' => "Thiếu tham số: $param"
				], 400);
			}
		}
		$speakerId = trim($_POST['speaker_id']);
		$speakerSpeed = (float) $_POST['speaker_speed'];
		$encodeType = (int) $_POST['encode_type'];
		if (!preg_match('/^[0-9]{1,4}$/', $speakerId) || $speakerSpeed < -3 || $speakerSpeed > 3 || !in_array($encodeType, [0, 1, 2, 3], true)) {
			vbotApiJsonResponse(['success' => false, 'error' => 'Thông số giọng Zalo TTS không hợp lệ'], 400);
		}
		$apiKeys = $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key'];
		foreach ($apiKeys as $apiKey) {
			$ch = curl_init();
			$postFields = http_build_query([
				'input' => $text,
				'speaker_id' => $speakerId,
				'speed' => $speakerSpeed,
				'encode_type' => $encodeType
			]);
			curl_setopt_array($ch, [
				CURLOPT_URL => 'https://api.zalo.ai/v1/tts/synthesize',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postFields,
				CURLOPT_HTTPHEADER => [
					"apikey: $apiKey",
					"Content-Type: application/x-www-form-urlencoded"
				],
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT => 60
			]);
			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpCode === 200) {
				$result = json_decode($response, true);
				if (isset($result['error_code']) && $result['error_code'] == 0) {
					$audioUrl = $result['data']['url'];
					$filename = sanitize_filename($text);
					$savePath = $ttsSaveDirectory . '/' . $filename;
					$audioData = downloadTtsAudio($audioUrl);
					if ($audioData !== false) {
						if (file_put_contents($savePath, $audioData, LOCK_EX) !== false) {
							@chmod($savePath, 0777);
							vbotApiJsonResponse([
								'success' => true,
								'message' => 'Tạo file âm thanh thành công',
								'file_path' => $savePath,
								'file_name' => $filename,
								//'api_key' => $apiKey,
								'url' => $audioUrl
							]);
						}
						error_log('TTS Zalo: không thể ghi file âm thanh '.$savePath);
					} else {
						error_log('TTS Zalo: không thể tải file âm thanh từ URL trả về.');
					}
				}
			}
		}
		vbotApiJsonResponse([
			'success' => false,
			'error' => 'Tất cả API key TTS Zalo đều thất bại.'
		], 502);
	}
	exit();
}
?>
