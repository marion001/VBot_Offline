<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

include '../../Configuration.php';
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

//Bật Logs php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

//Lấy giao thức (http hoặc https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$Cover_URL_Local = dirname(dirname(dirname($Current_URL)));

//Chuyển đổi thời gian
function formatDuration($duration)
{
	// Nếu duration là số (chứa toàn bộ ký tự là số)
	if (ctype_digit($duration)) {
		$hours = floor($duration / 3600);
		$minutes = floor(($duration % 3600) / 60);
		$seconds = $duration % 60;
		return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
	}
	// Nếu đã là định dạng HH:mm:ss, trả về nguyên bản
	return $duration;
}

//Chuyển đổi thời gian Youtube
function formatDuration_youtube($duration)
{
	if (ctype_digit($duration)) {
		$hours = floor($duration / 3600);
		$minutes = floor(($duration % 3600) / 60);
		$seconds = $duration % 60;
		return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
	}
	if (preg_match('/^PT/', $duration)) {
		try {
			$interval = new DateInterval($duration);
			$hours = $interval->h + ($interval->d * 24);
			$minutes = $interval->i;
			$seconds = $interval->s;
			return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
		} catch (Exception $e) {
			return $duration;
		}
	}
	if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
		return $duration;
	}
	return $duration;
}

// Hàm kiểm tra xem token podcast đã hết hạn chưa
function isTokenExpired_podcast($Config)
{
	$expire_time = $Config['media_player']['podcast']['expire_time'];
	if (!is_numeric($expire_time)) {
		return false;
	}
	$current_time = time();
	return $current_time > $expire_time;
}

// Hàm lấy lại token từ API
function refreshToken_podcast($Config, $VBot_Offline)
{
	//echo "Đang lấy lại token...\n";
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://users.iviet.com/v1/auth/login",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{"email":"' . base64_decode('dmlldGJvdHNtYXJ0c3BlYWtlckBnbWFpbC5jb20=') . '","password":"' . base64_decode('VmlldGJvdEAx') . '"}',
		CURLOPT_HTTPHEADER => array(
			'Host: users.iviet.com',
			'pragma: no-cache',
			'cache-control: no-cache',
			'sec-ch-ua: "Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
			'accept: application/json, text/plain, */*',
			'content-type: application/json',
			'dnt: 1',
			'sec-ch-ua-mobile: ?0',
			'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
			'sec-ch-ua-platform: "Windows"',
			'origin: https://app.maika.ai',
			'sec-fetch-site: cross-site',
			'sec-fetch-mode: cors',
			'sec-fetch-dest: empty',
			'referer: https://app.maika.ai/',
			'accept-language: vi'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	//echo $response;
	// Giải mã JSON để chuyển thành mảng PHP
	$responseData = json_decode($response, true);
	if ($responseData['code'] === 200) {
		// Lấy thời gian hiện tại tính bằng giây kể từ Epoch
		$currentTimestamp = time();
		$newTimestamp = $currentTimestamp + (12 * 3600);
		//echo $responseData['data']['access_token'];
		$Config['media_player']['podcast']['access_token'] = $responseData['data']['access_token'];
		$Config['media_player']['podcast']['expire_time'] = $newTimestamp;
		file_put_contents($VBot_Offline . 'Config.json', json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}

//Get danh sách các bài hát trong Local
if (isset($_GET['Local'])) {
	// Đường dẫn đến thư mục cần tìm kiếm
	$directory = $VBot_Offline . 'Media/Music_Local';
	// Các phần mở rộng tệp được phép
	$allowed_extensions = $Allowed_Extensions_Audio;
	// Mảng để lưu trữ kết quả
	$files_info = [];
	// Hàm kiểm tra phần mở rộng tệp
	function hasAllowedExtension($filename, $allowed_extensions)
	{
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return in_array($extension, $allowed_extensions);
	}
	// Hàm chuyển đổi byte sang MB
	function bytesToMB($bytes)
	{
		return number_format($bytes / 1048576, 2); // 1 MB = 1048576 bytes, định dạng với 2 chữ số thập phân

	}
	// Tìm tất cả các tệp âm thanh trong thư mục chính
	$items = scandir($directory);
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$path = $directory . '/' . $item;
		if (is_file($path) && hasAllowedExtension($item, $allowed_extensions)) {
			// Nếu là tệp và có phần mở rộng được phép
			$files_info[] = ['name' => $item, 'cover' => $Cover_URL_Local . '/assets/img/icon_audio_local.png', 'full_path' => $path, 'size' => bytesToMB(filesize($path))];
		}
	}
	echo json_encode($files_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit();
}

if (isset($_GET['audio_schedule'])) {
	//Đường dẫn đến thư mục cần tìm kiếm
	$directory = $VBot_Offline . $Config['schedule']['audio_path'];
	//Các phần mở rộng tệp được phép
	$allowed_extensions = $Allowed_Extensions_Audio;
	//Mảng để lưu trữ kết quả
	$files_info = [];
	//Hàm kiểm tra phần mở rộng tệp
	function hasAllowedExtension($filename, $allowed_extensions)
	{
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return in_array($extension, $allowed_extensions);
	}
	// Hàm chuyển đổi byte sang MB
	function bytesToMB($bytes)
	{
		// 1 MB = 1048576 bytes, định dạng với 2 chữ số thập phân
		return number_format($bytes / 1048576, 2);
	}
	// Tìm tất cả các tệp âm thanh trong thư mục chính
	$items = scandir($directory);
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$path = $directory . '/' . $item;
		if (is_file($path) && hasAllowedExtension($item, $allowed_extensions)) {
			// Nếu là tệp và có phần mở rộng được phép
			$files_info[] = ['name' => $item, 'cover' => $Cover_URL_Local . '/assets/img/icon_audio_local.png', 'full_path' => $path, 'size' => bytesToMB(filesize($path))];
		}
	}
	// Trả về kết quả dưới dạng JSON
	//header('Content-Type: application/json');
	echo json_encode($files_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit();
}

//Lấy Cookie ZingMP3
function getZmp3RqidCookie()
{
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://zingmp3.vn/",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_NOBODY => false,
		CURLOPT_COOKIEJAR => "",
		CURLOPT_COOKIEFILE => "",
		CURLOPT_TIMEOUT => 30,
	));
	$response = curl_exec($curl);
	if (curl_errno($curl)) {
		curl_close($curl);
		return ['error' => 'Lỗi cURL: ' . curl_error($curl)];
	}
	curl_close($curl);
	if (preg_match('/zmp3_rqid=([^;]+)/', $response, $matches)) {
		return ['zmp3_rqid' => $matches[1]];
	} else {
		return ['error' => 'Cookie zmp3_rqid không tìm thấy.'];
	}
}

function getLinkZingMP3($song_id, $Cookie_Zing)
{
	//echo "Cookie zmp3_rqid: " . $result_cookieZINGMP3['zmp3_rqid'] . "\n";
	$VERSION = "1.6.34";
	$path = "/api/v2/song/get/streaming";
	$SECRET_KEY = "2aa2d1c561e809b267f3638c4a307aab";
	$API_KEY = "88265e23d4284f25963e6eedac8fbfa3";
	$ctime = strval(time());
	// Tạo chuỗi hash
	$str_hash = "ctime={$ctime}id={$song_id}version={$VERSION}";
	$hash256 = hash('sha256', $str_hash);
	// Tạo HMAC signature
	$hmac_signature = hash_hmac('sha512', $path . $hash256, $SECRET_KEY);
	$url = "https://zingmp3.vn/api/v2/song/get/streaming?id={$song_id}&ctime={$ctime}&version={$VERSION}&sig={$hmac_signature}&apiKey={$API_KEY}";
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => array(
			"Cookie: zmp3_rqid=" . $Cookie_Zing
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	// Chuyển đổi phản hồi JSON thành mảng
	$response_data = json_decode($response, true);
	// Kiểm tra và xử lý kết quả
	if (isset($response_data['err']) && $response_data['err'] == 0 && isset($response_data['data']['128'])) {
		$url_128 = $response_data['data']['128'];
		//return ['success' => true, 'url' => $url_128, 'full_data' => $response_data];
		return ['success' => true, 'url' => $url_128];
	} else {
		return [
			'success' => false,
			//'error_message' => "Không lấy được link bài hát từ Zingmp3. Dữ liệu phản hồi: " . print_r($response_data, true)
			'error_message' => print_r($response_data['msg'], true)
		];
	}
}

//Get các đài báo Radio
if (isset($_GET['Radio'])) {
	// Mảng chứa thông tin radio
	$radio_info = [];
	if ($Config['media_player']['radio_data'] && isset($Config['media_player']['radio_data'])) {
		foreach ($Config['media_player']['radio_data'] as $radio) {
			$name = $radio['name'];
			$link = $radio['link'];
			// Thêm dữ liệu vào mảng $radio_info
			$radio_info[] = [
				'name' => $name,
				'cover' => null,
				'full_path' => $link,
				'size' => null
			];
		}
	}
	//header('Content-Type: application/json');
	echo json_encode($radio_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit();
}

//Get link Zingmp3 thành link player
if (isset($_GET['ZingMP3_GetLink'])) {
	$ZingMP3_id = isset($_GET['Zing_ID']) ? urlencode($_GET['Zing_ID']) : '';
	$result_cookieZINGMP3 = getZmp3RqidCookie();
	if (isset($result_cookieZINGMP3['error'])) {
		echo json_encode(array(
			'success' => false,
			'error' => $result_cookieZINGMP3['error']
		));
		exit();
	} else {
		$Link_Zing_mp3 = getLinkZingMP3($ZingMP3_id, $result_cookieZINGMP3['zmp3_rqid']);
		echo json_encode($Link_Zing_mp3);
	}
}

// tìm kiếm Zingmp3
if (isset($_GET['ZingMP3_Search'])) {
	$Song_Name = isset($_GET['SongName']) ? urlencode($_GET['SongName']) : '';
	// Kiểm tra nếu biến Song_Name không có dữ liệu
	if (empty($Song_Name)) {
		// Tạo mảng thông báo lỗi
		$response = array(
			'success' => false,
			'message' => 'Tên bài hát không được cung cấp.'
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	$zingJsonPath = '../cache/ZingMP3.json';
	if (!file_exists($zingJsonPath)) {
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($zingJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($zingJsonPath, 0777);
	}
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "http://ac.mp3.zing.vn/complete?type=song&num=10&query=" . $Song_Name,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
	));
	$response = curl_exec($curl);
	if (curl_errno($curl)) {
		curl_close($curl);
		echo json_encode(array(
			'success' => false,
			'error' => 'Lỗi cURL: ' . curl_error($curl)
		));
		exit();
	}
	curl_close($curl);
	$data = json_decode($response, true);
	//echo $response;
	if ($data['result'] && isset($data['data'][0]['song'])) {
		$songs = $data['data'][0]['song'];
		$results = array();
		foreach ($songs as $songData) {
			$ZingMP3_id = $songData['id'];
			$ZingMP3_name = $songData['name'];
			$ZingMP3_artist = $songData['artist'] ?? $songData['artists'] ?? 'N/A';
			$ZingMP3_duration = $songData['duration'];
			$ZingMP3_thumb = 'https://photo-zmp3.zmdcdn.me/' . $songData['thumb'];
			// Gọi hàm getLinkZingMP3 để lấy liên kết
			$results[] = array(
				'id' => $ZingMP3_id,
				//'name' => $ZingMP3_name,
				'name' => str_replace(["'", '"'], '', $ZingMP3_name ?? 'N/A'),
				'artist' => $ZingMP3_artist,
				'duration' => str_pad(floor($ZingMP3_duration / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad($ZingMP3_duration % 60, 2, '0', STR_PAD_LEFT),
				'thumb' => $ZingMP3_thumb,
				'url' => null
			);
		}
		echo json_encode(array(
			'success' => true,
			'results' => $results
		));
		//Cache Ghi đè toàn bộ nội dung vào file ZingMP3.json
		file_put_contents($zingJsonPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	} else {
		echo json_encode(array(
			'success' => false,
			'message' => 'Không tìm thấy bài hát.'
		));
	}
	exit();
}

// get dữ liệu cache Zing
if (isset($_GET['Cache_ZingMP3'])) {
	$zingmp3JsonPath = "../cache/ZingMP3.json";
	if (!file_exists($zingmp3JsonPath)) {
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($zingmp3JsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($zingmp3JsonPath, 0777);
	}
	$jsonData = file_get_contents($zingmp3JsonPath);
	$data = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} else {
		echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit();
}

//GetLink Các Trang Báo
if (isset($_GET['Get_Link_NewsPaper'])) {
	$URL = isset($_GET['url']) ? $_GET['url'] : '';
	// Kiểm tra nếu biến URL không có dữ liệu
	if (empty($URL)) {
		$response = array(
			'success' => false,
			'message' => 'Cần nhập URL,Link Báo',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	if (strpos($URL, "vietnamnet.vn") !== false) {
		$html = file_get_contents($URL);
		if ($html === false) {
			echo json_encode([
				'success' => false,
				'message' => 'Lỗi khi lấy dữ liệu từ trang VietNamNet',
				'data' => null
			]);
			exit;
		}
		preg_match('/<div class="vnn-audio podcast-audio">.*?<source src="([^"]+)"/s', $html, $matches);
		if (!empty($matches[1])) {
			echo json_encode([
				'success' => true,
				'message' => 'Thành Link từ báo VietNamNet Thành Công',
				'data' => [
					'audio_link' => $matches[1]
				]
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Không tìm thấy âm thanh trong trang',
				'data' => null
			]);
		}
		exit();
	}else if (strpos($URL, "tienphong.vn") !== false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'); // giả lập trình duyệt
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$html = curl_exec($ch);
		$err = curl_error($ch);
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		if ($html === false || !$html) {
			echo json_encode([
				'success' => false,
				'message' => 'Lỗi khi lấy dữ liệu từ trang Báo Tiền Phong: ' . $err,
				'data' => null
			]);
			exit;
		}
		if (preg_match('/<div[^>]+class=["\']hidden-player["\'][^>]+data-src=["\']([^"\']+)["\']/', $html, $matches)) {
			$audioLink = $matches[1];
			echo json_encode([
				'success' => true,
				'message' => 'Lấy Link Player Từ Báo Tiền Phong Thành Công',
				'data' => [
					'audio_link' => $audioLink
				]
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Không tìm thấy thẻ audio chứa đường dẫn phát trên Báo Tiền Phong',
				'data' => null
			]);
		}
		exit();
	}else if (strpos($URL, "baomoi.com") !== false) {
		if (strpos($URL, '#') !== false) {
			$fragments = explode('#', $URL);
			$last_fragment = end($fragments);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Không tìm ID trong URL',
				'data' => null
			]);
			exit;
		}
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://w-api.baomoi.com/api/v1/page/get/audio-detail?id=' . $last_fragment,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
		));
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ($http_code !== 200 || $response === false) {
			echo json_encode([
				'success' => false,
				'message' => 'Không thể kết nối tới API Báo Mới hoặc mã trạng thái HTTP không hợp lệ.',
				'data' => null
			]);
			exit;
		}
		if (preg_match('/"streamingUrl"\s*:\s*"([^"]+)"/', $response, $matches)) {
			$streamingUrl = $matches[1];
			echo json_encode([
				'success' => true,
				'message' => 'Lấy Link Player Từ Báo Mới Thành Công',
				'data' => [
					'audio_link' => $streamingUrl
				]
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Không tìm thấy dữ liệu âm thanh trong phản hồi.',
				'data' => null
			]);
		}
		exit();
	}
	else if (strpos($URL, "24h.com.vn") !== false) {
		$html = @file_get_contents($URL);
		if ($html) {
			if (preg_match('/<source[^>]+src=["\']([^"\']+\.mp3)[^"\']*["\']/i', $html, $m)) {
				$audio_src = trim($m[1]);
				$audio_final = preg_replace('/\.mp3$/i', '---nu_bac.mp3', $audio_src);
				echo json_encode([
					'success' => true,
					'message' => 'Lấy Link Player Từ Báo 24h.com.vn Thành Công',
					'data' => [
						'audio_link' => $audio_final
					]
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => 'Không tìm thấy dữ liệu âm thanh trong phản hồi.',
					'data' => null
				]);
			}
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Không tải được dữ liệu từ báo 24h.com.vn',
				'data' => null
			]);
		}
	}
	else {
		echo json_encode([
			'success' => false,
			'message' => 'Nguồn Trang Báo, Tin Tức Này Chưa Được Hỗ Trợ',
			'data' => null
		]);
	}
	exit();
}

//Tìm kiếm podcast
if (isset($_GET['podcast_Search'])) {
	// Bây giờ bạn có thể sử dụng $tokenData['data']['access_token'] để gửi các yêu cầu API khác
	$podcast_Name = isset($_GET['PodCastName']) ? $_GET['PodCastName'] : '';
	$podcast_Limit = isset($_GET['Limit']) ? $_GET['Limit'] : '1';

	// Kiểm tra nếu biến Song_Name không có dữ liệu
	if (empty($podcast_Name)) {
		$response = array(
			'success' => false,
			'message' => 'Cần nhập dữ liệu để tìm kiếm',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	$podcastJsonPath = '../cache/PodCast.json';
	if (!file_exists($podcastJsonPath)) {
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($podcastJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($podcastJsonPath, 0777);
	}

	if (isTokenExpired_podcast($Config)) {
		// Nếu token đã hết hạn, làm mới token
		refreshToken_podcast($Config, $VBot_Offline);
	}

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://core.ocs.iviet.com/v1/graphql',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{"operationName":"Search","query":"query Search($keyword: String!, $category: [String]!, $offset: Int!, $limit: Int!) {\\n  search(\\n    q: $keyword\\n    offset: $offset\\n    limit: $limit\\n    filter: {media: {types: $category}}\\n  ) {\\n    __typename\\n    episode {\\n      __typename\\n      audio\\n      duration\\n      id\\n      is_gcs\\n      published_at\\n      title\\n      media {\\n        __typename\\n        audio\\n        cover\\n        created_at\\n        id\\n        slug\\n        title\\n        total_episode\\n        content_type {\\n          __typename\\n          description\\n          value\\n        }\\n      }\\n      authors\\n      description\\n    }\\n    media {\\n      __typename\\n      cover\\n      created_at\\n      is_list\\n      slug\\n      title\\n      total_episode\\n      type\\n      id\\n      content_type {\\n        __typename\\n        description\\n        value\\n      }\\n    }\\n  }\\n}","variables":{"category":[],"keyword":"' . $podcast_Name . '","limit":' . $podcast_Limit . ',"offset":0}}',
		CURLOPT_HTTPHEADER => array(
			'Host: core.ocs.iviet.com',
			'content-type: application/json',
			'accept: */*',
			'apollographql-client-version: 3.2.5-401',
			'authorization: Bearer ' . $Config['media_player']['podcast']['access_token'],
			'source: ios',
			'device-type: ios',
			'accept-language: vi-VN,vi;q=0.9',
			'x-apollo-operation-type: query',
			'user-agent: MAIKA/401 CFNetwork/1408.0.4 Darwin/22.5.0',
			'apollographql-client-name: com.olli.omni-apollo-ios',
			'x-apollo-operation-name: Search'
		),
	));

	$response_podcast = curl_exec($curl);
	curl_close($curl);
	//echo $response_podcast;
	$podcast_Data = json_decode($response_podcast, true);
	$result = [
		'success' => false, // Mặc định là false, sẽ cập nhật thành true nếu có dữ liệu hợp lệ
		'message' => ' không có dữ liệu', // Thông báo mặc định khi không có dữ liệu
		'data' => []
	];

	// Kiểm tra và trích xuất thông tin từ phần 'search'
	if (isset($podcast_Data['data']['search']) && is_array($podcast_Data['data']['search'])) {
		$baseUrl = "https://cdn-ocs.iviet.com/";
		foreach ($podcast_Data['data']['search'] as $entry) {
			if (isset($entry['episode'])) {
				$audioUrl = $entry['episode']['audio'] ?? 'N/A';
				$coverUrl = $entry['episode']['media']['cover'] ?? 'N/A';
				$duration = $entry['episode']['duration'] ?? 'N/A';
				$description = $entry['episode']['media']['content_type']['description'] ?? 'N/A';
				// Kiểm tra nếu audio không bắt đầu bằng "http"
				if ($audioUrl !== 'N/A' && strpos($audioUrl, 'http') !== 0) {
					$audioUrl = $baseUrl . $audioUrl;
				}
				// Kiểm tra nếu cover không bắt đầu bằng "http"
				if ($coverUrl !== 'N/A' && strpos($coverUrl, 'http') !== 0) {
					$coverUrl = $baseUrl . $coverUrl;
				}
				// Định dạng lại duration
				$formattedDuration = formatDuration($duration);
				$result['data'][] = [
					//'title' => $entry['episode']['title'] ?? 'N/A',
					'title' => str_replace(["'", '"'], '', $entry['episode']['title'] ?? 'N/A'),
					'cover' => $coverUrl,
					'audio' => $audioUrl,
					'duration' => $formattedDuration,
					'description' => $description
				];
				$result['success'] = true; // Cập nhật success thành true nếu tìm thấy dữ liệu
			}
		}
		if ($result['success']) {
			$result['message'] = 'Dữ liệu được truy xuất thành công';
		}
		//Cache Ghi đè toàn bộ nội dung vào file ZingMP3.json
		file_put_contents($podcastJsonPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
	echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit();
}

// get dữ liệu cache PodCast
if (isset($_GET['Cache_PodCast'])) {
	$podcastJsonPath = '../cache/PodCast.json';
	if (!file_exists($podcastJsonPath)) {
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($podcastJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($podcastJsonPath, 0777);
	}
	$jsonData = file_get_contents($podcastJsonPath);
	$data = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} else {
		echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit();
}

//Tìm kiếm Youtube
if (isset($_GET['Youtube_Search'])) {
	$Youtube_Name = $_GET['Name'] ?? '';
	$Youtube_Limit = $_GET['Limit'] ?? '20';
	if (empty($Youtube_Name)) {
		$responseb = [
			'success' => false,
			'message' => 'Cần nhập dữ liệu để tìm kiếm',
			'data' => []
		];
		echo json_encode($responseb, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	$youtubeJsonPath = '../cache/Youtube.json';
	if (!file_exists($youtubeJsonPath)) {
		file_put_contents($youtubeJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($youtubeJsonPath, 0777);
	}
	$apiKey = $Config['media_player']['youtube']['google_apis_key'];
	$searchUrlYoutube = "https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&q=" . urlencode($Youtube_Name) . "&maxResults=" . $Youtube_Limit . "&key=" . $apiKey;
	$curlYoutube = curl_init();
	curl_setopt_array($curlYoutube, [
		CURLOPT_URL => $searchUrlYoutube,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
	]);
	$responseYoutube = curl_exec($curlYoutube);
	curl_close($curlYoutube);
	if ($responseYoutube === false) {
		echo json_encode(['error' => 'Yêu cầu cURL không thành công.']);
	} else {
		$dataYoutube = json_decode($responseYoutube, true);
		if (isset($dataYoutube['error'])) {
			echo json_encode(['error' => 'YouTube trả về lỗi: ' . $dataYoutube['error']['message']]);
		} elseif (empty($dataYoutube['items'])) {
			echo json_encode(['error' => 'Không có dữ liệu được tìm thấy.']);
		} else {
			$videoIds = [];
			foreach ($dataYoutube['items'] as $itemYoutube) {
				if (isset($itemYoutube['id']['videoId'])) {
					$videoIds[] = $itemYoutube['id']['videoId'];
				}
			}
			// Gọi API videos để lấy duration
			$durationMap = [];
			if (!empty($videoIds)) {
				$videosUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=" . implode(",", $videoIds) . "&key=" . $apiKey;
				$curlVideos = curl_init();
				curl_setopt_array($curlVideos, [
					CURLOPT_URL => $videosUrl,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
				]);
				$responseVideos = curl_exec($curlVideos);
				curl_close($curlVideos);
				$dataVideos = json_decode($responseVideos, true);
				if (!empty($dataVideos['items'])) {
					foreach ($dataVideos['items'] as $video) {
						$id = $video['id'];
						$duration = $video['contentDetails']['duration'] ?? '';
						$durationMap[$id] = formatDuration_youtube($duration);
					}
				}
			}
			$items = [];
			foreach ($dataYoutube['items'] as $itemYoutube) {
				$vid = $itemYoutube['id']['videoId'] ?? 'N/A';
				$items[] = [
					'title' => str_replace(["'", '"'], '', $itemYoutube['snippet']['title'] ?? 'N/A'),
					'id' => $vid,
					'channelTitle' => $itemYoutube['snippet']['channelTitle'] ?? 'N/A',
					'link' => $vid !== 'N/A' ? "https://www.youtube.com/watch?v=" . $vid : '',
					'cover' => $itemYoutube['snippet']['thumbnails']['high']['url'] ?? '',
					'description' => $itemYoutube['snippet']['description'] ?? '',
					'duration' => $durationMap[$vid] ?? ''
				];
			}
			$responsez = [
				'success' => true,
				'message' => 'Tìm kiếm thành công',
				'data' => $items
			];
			echo json_encode($responsez, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			file_put_contents($youtubeJsonPath, json_encode($responsez, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}
	}
	exit();
}

if (isset($_GET['Cache_Youtube'])) {
	$youtubeJsonPath = '../cache/Youtube.json';
	if (!file_exists($youtubeJsonPath)) {
		file_put_contents($youtubeJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($youtubeJsonPath, 0777);
	}
	$jsonData = file_get_contents($youtubeJsonPath);
	$data = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} else {
		echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit();
}

#get Link Youtube
if (isset($_GET['GetLink_Youtube'])) {
	// Lấy ID video YouTube từ request
	$Youtube_ID = isset($_GET['Youtube_ID']) ? $_GET['Youtube_ID'] : '';
	// Kiểm tra xem ID video có được cung cấp hay không
	if (empty($Youtube_ID)) {
		$response = array(
			'success' => false,
			'message' => 'Cần nhập ID của video Youtube',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	$title = "N/A (Không Xác Định Được Tên)";
	$thumbnails = "https://img.youtube.com/vi/$Youtube_ID/0.jpg";

	$url_api_ytb = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $Youtube_ID . '&key=' . $Config['media_player']['youtube']['google_apis_key'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_api_ytb);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'Lỗi cURL: ' . curl_error($ch);
		curl_close($ch);
		exit;
	}
	curl_close($ch);
	$data_api_ytb = json_decode($response, true);
	if (isset($data_api_ytb['items'][0]['snippet']['title'])) {
		$title = $data_api_ytb['items'][0]['snippet']['title'];
		$thumbnails = $data_api_ytb['items'][0]['snippet']['thumbnails']['high']['url'];
	}

	// Câu lệnh gọi Python script
	$CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Get_Link_Youtube.py $Youtube_ID");
	// Kết nối SSH
	$connection = ssh2_connect($ssh_host, $ssh_port);
	if (!$connection) {
		$response = array(
			'success' => false,
			'message' => 'Không thể kết nối tới máy chủ SSH.',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
		$response = array(
			'success' => false,
			'message' => 'Xác thực SSH không thành công.',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	// Thực thi câu lệnh Python trên máy chủ SSH
	$stream = ssh2_exec($connection, $CMD);
	if (!$stream) {
		$response = array(
			'success' => false,
			'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
			'data' => []
		);
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	// Chuyển sang chế độ đồng bộ để đợi kết quả
	stream_set_blocking($stream, true);
	$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
	$output = stream_get_contents($stream_out);
	if ($output) {
		$response = array(
			'success' => true,
			'message' => 'Lấy link thành công.',
			'data' => array(
				'dlink' => trim($output),
				'cover' => $thumbnails,
				'title' => $title
			)
		);
	} else {
		$response = array(
			'success' => false,
			'message' => 'Không nhận được dữ liệu trả về từ Python script.',
			'data' => []
		);
	}
	fclose($stream);
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

//lấy dữ liệu cache báo, tin tức
if (isset($_GET['Cache_NewsPaper'])) {
	$newspaperJsonPath = '../cache/News_Paper.json';
	if (!file_exists($newspaperJsonPath)) {
		$initialData = [
			'success' => true,
			'message' => 'Danh sách báo, tin tức',
			'data' => []
		];
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($newspaperJsonPath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($newspaperJsonPath, 0777);
	}
	$jsonData = file_get_contents($newspaperJsonPath);
	$data = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} else {
		echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit();
}

//Hiển thị dữ liệu playlist
if (isset($_GET['Cache_PlayList'])) {
	$playlistJsonPath = '../cache/PlayList.json';
	if (!file_exists($playlistJsonPath)) {
		$initialData = [
			'success' => true,
			'message' => 'Danh sách phát tổng hợp',
			'data' => []
		];
		// Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
		file_put_contents($playlistJsonPath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($playlistJsonPath, 0777);
	}
	$jsonData = file_get_contents($playlistJsonPath);
	$data = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} else {
		echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit();
}

// Thêm bài hát vào playlist
if (isset($_GET['playlist_ADD'])) {
	$filePath = '../cache/PlayList.json';
	$response = [];
	if (!file_exists($filePath)) {
		$initialData = [
			'success' => true,
			'message' => 'Danh sách phát tổng hợp',
			'data' => []
		];
		// Ghi dữ liệu mặc định vào file và thiết lập quyền 777
		file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		chmod($filePath, 0777);
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$requiredFields = ['title', 'cover', 'audio', 'duration', 'description', 'source', 'id', 'channelTitle', 'artist'];
		$missingFields = [];
		foreach ($requiredFields as $field) {
			if (!isset($_POST[$field]) || empty($_POST[$field])) {
				$missingFields[] = $field;
			}
		}
		if (!empty($missingFields)) {
			$response['success'] = false;
			$response['message'] = 'Thiếu dữ liệu: ' . implode(', ', $missingFields);
			echo json_encode($response);
			exit;
		}
		// Tạo ID ngẫu nhiên với 6 ký tự
		function generateRandomId($length = 6)
		{
			return strtoupper(bin2hex(random_bytes($length / 2)));
		}
		// Hàm để kiểm tra và chuyển đổi các giá trị "null" và rỗng thành null
		function convertToNull($value)
		{
			return $value === 'null' || $value === '' ? null : $value;
		}
		// Lấy dữ liệu từ form và đặt giá trị "null" nếu không tồn tại hoặc rỗng
		$newEntry = [
			'ids_list' => generateRandomId(),
			'title' => convertToNull($_POST['title']),
			'cover' => convertToNull($_POST['cover']),
			'audio' => convertToNull($_POST['audio']),
			'duration' => convertToNull($_POST['duration']),
			'description' => convertToNull($_POST['description']),
			'source' => convertToNull($_POST['source']),
			'id' => convertToNull($_POST['id']),
			'channelTitle' => convertToNull($_POST['channelTitle']),
			'artist' => convertToNull($_POST['artist'])
		];
		$jsonData = file_get_contents($filePath);
		$data = json_decode($jsonData, true);
		// Kiểm tra trùng lặp
		$isDuplicate = false;
		foreach ($data['data'] as $entry) {
			if ($entry['title'] === $newEntry['title'] && $entry['source'] === $newEntry['source'] && $entry['artist'] === $newEntry['artist'] && $entry['cover'] === $newEntry['cover']) {
				$isDuplicate = true;
				break;
			}
		}
		if ($isDuplicate) {
			$response['success'] = false;
			$response['message'] = ' Bài hát ' . $_POST['title'] . ' đã tồn tại trong danh sách phát ở nguồn ' . $_POST['source'];
		} else {
			// Thêm dữ liệu mới vào mảng "data"
			$data['data'][] = $newEntry;
			// Chuyển đổi mảng thành JSON
			$jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			// Ghi dữ liệu JSON vào file
			if (file_put_contents($filePath, $jsonData)) {
				$response['success'] = true;
				$response['message'] = 'Dữ liệu đã được ghi vào file JSON thành công.';
			} else {
				$response['success'] = false;
				$response['message'] = 'Lỗi: Không thể ghi dữ liệu vào file JSON.';
			}
		}
	} else {
		$response['success'] = false;
		$response['message'] = 'Chỉ chấp nhận các yêu cầu POST.';
	}
	echo json_encode($response);
	exit();
}

//Xóa playlist hoặc xóa 1 hay nhiều bài hát theo ids_list
if (isset($_GET['playlist_DELETE'])) {
	$filePath = '../cache/PlayList.json';
	if (file_exists($filePath)) {
		$jsonData = file_get_contents($filePath);
		$data = json_decode($jsonData, true);
		// Đảm bảo rằng 'data' là một mảng
		if (!isset($data['data']) || !is_array($data['data'])) {
			$data['data'] = [];
		}
		// Xử lý yêu cầu xóa
		$action = isset($_POST['action']) ? $_POST['action'] : null;
		$idsToDelete = isset($_POST['ids_list']) ? $_POST['ids_list'] : null;
		$updatedData = $data['data'];
		$idsToDelete = $idsToDelete ? explode(',', $idsToDelete) : [];
		$deletedIds = [];
		$idsNotFound = [];
		if ($action === 'delete_all') {
			// Xóa toàn bộ nội dung trong 'data'
			$updatedData = [];
			$deletedIds = array_column($data['data'], 'ids_list');
		} elseif ($action === 'delete_some' && !empty($idsToDelete)) {
			// Xóa các mục theo 'ids_list'
			$existingIds = array_column($updatedData, 'ids_list');
			$idsNotFound = array_diff($idsToDelete, $existingIds);
			// Lọc các mục không nằm trong danh sách 'idsToDelete'
			foreach ($idsToDelete as $id) {
				if (in_array($id, $existingIds)) {
					$deletedIds[] = $id;
				}
			}
			$updatedData = array_filter($updatedData, function ($entry) use ($idsToDelete) {
				return !in_array($entry['ids_list'], $idsToDelete);
			});
			// Đặt lại chỉ số của mảng
			$updatedData = array_values($updatedData);
		} else {
			// Nếu yêu cầu không hợp lệ
			$response = [
				'success' => false,
				'message' => 'Yêu cầu không hợp lệ.',
			];
			echo json_encode($response);
			exit;
		}
		$fileContent = [
			'success' => true,
			'message' => "Danh sách phát tổng hợp",
			'data' => $updatedData
		];
		if (file_put_contents($filePath, json_encode($fileContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
			// Phản hồi với thông tin về các mục đã xóa và các ID không tìm thấy
			$response = [
				'success' => true,
				'message' => 'Danh sách phát đã được cập nhật.',
				'data' => [
					'ids_deleted' => $deletedIds,
					'ids_not_found' => $idsNotFound
				]
			];
			echo json_encode($response);
		} else {
			$responsexx = [
				'success' => false,
				'message' => 'Lỗi: Không thể ghi dữ liệu vào file JSON.'
			];
			echo json_encode($responsexx);
		}
	} else {
		// Nếu file không tồn tại
		$responsezz = [
			'success' => false,
			'message' => 'File không tồn tại.'
		];
		echo json_encode($responsezz);
	}
	exit();
}

//Xóa cache dữ liệu bài hát
if (isset($_GET['cache_delete']) && in_array($_GET['cache_delete'], ['ZingMP3', 'Youtube', 'PodCast'])) {
	// Khởi tạo mảng phản hồi
	$response = array('success' => false, 'message' => '');
	// Đường dẫn đến file JSON dựa trên giá trị 'cache_delete'
	$cacheType = $_GET['cache_delete'];
	$jsonFilePath = '../cache/' . $cacheType . '.json';
	// Kiểm tra xem file có tồn tại không
	if (file_exists($jsonFilePath)) {
		// Mở file JSON ở chế độ ghi
		$fileHandle = fopen($jsonFilePath, 'w');
		if ($fileHandle) {
			// Ghi một mảng rỗng vào file
			fwrite($fileHandle, '[]');
			fclose($fileHandle);
			$response['success'] = true;
			$response['message'] = "Đã xóa cache $cacheType";
			echo json_encode($response);
		} else {
			$response['message'] = "Không thể mở file để ghi.";
			echo json_encode($response);
		}
	} else {
		$response['message'] = "File " . $cacheType . ".json không tồn tại.";
		echo json_encode($response);
	}
	exit();
}

#Lấy Dữ liệu Báo, tin tức
if (isset($_GET['newspaper'])) {
	$News_Paper = isset($_GET['link']) ? $_GET['link'] : '';
	$response = [
		'success' => false,
		'message' => 'Lỗi không xác định',
		'data' => []
	];
	if (empty($News_Paper)) {
		$response['message'] = 'Cần nhập dữ liệu để tìm kiếm';
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	$filePath = "../cache/" . $Config['media_player']['news_paper']['newspaper_file_name'];
	//Báo vnexpress
	if (strpos($News_Paper, "vnexpress.net") !== false) {
		$apiUrl = "https://api3.vnexpress.net/api/article?type=get_topstory&cate_id=1004685&site_id=1000000&showed_area=trangchu_podcast_v2&app_id=9e304d";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$apiResponse = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($apiResponse !== false) {
			$data = json_decode($apiResponse, true);
			if (isset($data['error']) && $data['error'] === 0) {
				if (isset($data['data']['trangchu_podcast']) && is_array($data['data']['trangchu_podcast'])) {
					$result = ["data" => []];
					foreach ($data['data']['trangchu_podcast'] as $podcast) {
						$result["data"][] = [
							//"title" => $podcast['title'],
							"title" => str_replace(["'", "\""], "", $podcast['title']),
							"audio" => $podcast['podcast']['path'],
							"cover" => $podcast['podcast']['thumb_url'],
							"duration" => isset($podcast['podcast']['duration']) ? formatDuration($podcast['podcast']['duration']) : "N/A",
							"publish_time" => isset($podcast['publish_time']) ? date("d/m/Y, H:i", $podcast['publish_time']) : "N/A",
							"source" => "Báo VnExpress"
						];
					}
					if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
						$response['success'] = true;
						$response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
						$response['data'] = $result["data"];
					} else {
						$response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
					}
				} else {
					$response['message'] = 'Không tìm thấy dữ liệu podcast';
				}
			} else {
				$response['message'] = 'Lỗi từ API hoặc không thể lấy dữ liệu';
			}
		} else {
			$response['message'] = 'Lỗi cURL: ' . $error;
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
	//Báo Thanh Niên
	else if (strpos($News_Paper, "thanhnien.vn") !== false) {
		$response['message'] = "Nguồn dữ liệu Báo Thanh Niên không còn được hỗ trợ";
	}
	//Báo tuổi Trẻ
	else if (strpos($News_Paper, "podcast.tuoitre.vn") !== false) {
		$html = file_get_contents($News_Paper);
		if ($html === false) {
			$response['message'] = "Không thể lấy dữ liệu từ Báo: $News_Paper";
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}
		$pattern = '/<a[^>]*data-file="([^"]+)"[^>]*data-title="([^"]+)"[^>]*data-avatar="([^"]+)"[^>]*>/';
		$result = ["data" => []];
		$titles_seen = [];
		if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$title = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
				if (!in_array($title, $titles_seen)) {
					$result["data"][] = [
						"title" => str_replace(["'", "\""], "", $title),
						"audio" => $match[1],
						"cover" => $match[3],
						"duration" => "N/A",
						"publish_time" => "N/A",
						"source" => "Báo Tuổi Trẻ"
					];
					$titles_seen[] = $title;
				}
			}
			if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
				$response['success'] = true;
				$response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
				$response['data'] = $result["data"];
			} else {
				$response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
			}
		} else {
			$response['message'] = "Không tìm thấy dữ liệu phù hợp";
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
	//Báo Tiền Phong
	else if (strpos($News_Paper, "tienphong.vn") !== false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $News_Paper);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36');
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$html = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);
		$response = ['success' => false, 'message' => '', 'data' => []];
		if ($html === false || !$html) {
			$response['message'] = "Không thể lấy dữ liệu từ báo: " . $News_Paper . " - Lỗi: " . $err;
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}
		$result = ["data" => []];
		$unique_hrefs = [];
		$result_data = [];
		preg_match_all('/<figure[^>]*>.*?<a\s+class="cms-link"[^>]*href="([^"]+)"[^>]*title="([^"]+)"[^>]*>.*?<\/a>/is', $html, $matches);
		if (count($matches) > 2) {
			$links = $matches[1];
			$titles = $matches[2];
			foreach ($links as $index => $href) {
				$href = trim($href);
				$title = isset($titles[$index]) ? trim($titles[$index]) : 'N/A';
				$img_src = 'N/A';
				if (preg_match('/<figure[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\']/is', $matches[0][$index], $img_match)) {
					$img_src = trim($img_match[1]);
				}
				if (!in_array($href, $unique_hrefs)) {
					$unique_hrefs[] = $href;
					$result_data[] = [
						"href" => $href,
						"title" => $title,
						"cover" => $img_src
					];
				}
			}
			foreach (array_slice($result_data, 0, 10) as $data) {
				$link = $data['href'];
				$title = $data['title'];
				$thumb = $data['cover'];
				if ($thumb && $title && $link) {
					$result["data"][] = [
						"cover" => $thumb,
						"title" => str_replace(["'", "\""], "", $title),
						"audio" => $link,
						"source" => "Báo Tiền Phong"
					];
				}
			}
			if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
				$response['success'] = true;
				$response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
				$response['data'] = $result["data"];
			} else {
				$response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
			}
		} else {
			$response['message'] = "Không tìm thấy liên kết hợp lệ trong trang.";
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
	//Báo Mới
	else if (strpos($News_Paper, "baomoi.com/audio") !== false) {
		$html = file_get_contents($News_Paper);
		if ($html === FALSE) {
			$response['message'] = "Không thể lấy dữ liệu từ báo: " . $News_Paper;
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}
		preg_match('/<script\s+id="__NEXT_DATA__"\s+type="application\/json">(.*?)<\/script>/s', $html, $matches);
		if (!empty($matches[1])) {
			$result = ["data" => []];
			$json_data = json_decode($matches[1], true);
			if (isset($json_data['props']['pageProps']['resp']['data']['content']['items'])) {
				$items = $json_data['props']['pageProps']['resp']['data']['content']['items'];
				foreach (array_slice($items, 0, 20) as $item) {
					$title = isset($item['title']) ? $item['title'] : 'N/A';
					$thumb = isset($item['thumb']) ? $item['thumb'] : 'N/A';
					$duration = isset($item['duration']) ? $item['duration'] : 'N/A';
					$date = isset($item['date']) ? $item['date'] : null;
					$id_name = isset($item['id']) ? $item['id'] : 'N/A';
					$url_item = "https://baomoi.com" . (isset($item['url']) ? $item['url'] : 'N/A');
					$short_name = isset($item['publisher']['shortName']) ? $item['publisher']['shortName'] : 'N/A';
					if ($date) {
						$formatted_date = date("d/m/Y, H:i", $date);
					} else {
						$formatted_date = 'N/A';
					}
					if ($title && $id_name && $thumb) {
						$result["data"][] = [
							"title" => str_replace(["'", "\""], "", $title),
							"id" => $id_name,
							"audio" => $url_item . '#' . $id_name,
							"duration" => $duration,
							"publish_time" => $formatted_date,
							"cover" => $thumb,
							"short_name" => $short_name,
							"source" => "Báo Mới"
						];
					}
				}
				if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
					$response['success'] = true;
					$response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
					$response['data'] = $result["data"];
				} else {
					$response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
				}
			} else {
				$response['message'] = "Không tìm thấy thông tin về các bài viết.";
			}
		} else {
			$response['message'] = "Không tìm thấy thẻ script chứa dữ liệu JSON.";
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
	//Báo Việt Nam Nét
	else if (strpos($News_Paper, "vietnamnet.vn/podcast") !== false) {
		$html = file_get_contents($News_Paper);
		$printedTitles = [];
		$pattern = '/<div\s+class=["\'][^"\']*horizontalPost__avt[^"\']*["\'].*?>.*?<\/div>/s';
		preg_match_all($pattern, $html, $matches);
		$pattern1 = '/<div\s+class=["\'][^"\']*horizontalPost__main-actions[^"\']*["\'].*?>.*?<\/div>/s';
		preg_match_all($pattern1, $html, $matches1);
		if (!empty($matches[0]) && !empty($matches1[0])) {
			$result = ["data" => []];
			$count = min(count($matches[0]), count($matches1[0]));
			for ($i = 0; $i < $count; $i++) {
				$divAvt = $matches[0][$i];
				preg_match('/<a\s+href=["\'](.*?)["\'].*?title=["\'](.*?)["\']/', $divAvt, $linkMatches);
				preg_match('/<picture.*?>.*?<source[^>]+data-srcset=["\']([^"\']+)["\']/s', $divAvt, $imgMatches);
				$divMain = $matches1[0][$i];
				preg_match('/<span\s+class=["\']total-timer["\']>(.*?)<\/span>/s', $divMain, $timeMatches);
				preg_match('/<span\s+class=["\']public-date["\']>(.*?)<\/span>/s', $divMain, $dateMatches);
				$title = htmlspecialchars(trim($linkMatches[2]));
				if (in_array($title, $printedTitles)) {
					continue;
				}
				$COVER = null;
				if (!empty($imgMatches)) {
					$COVER = trim($imgMatches[1]);
				} else {
					preg_match('/<img\s+[^>]*data-srcset=["\']([^"\']+)["\']/s', $divAvt, $imgFallbackMatches);
					if (!empty($imgFallbackMatches)) {
						$COVER = trim($imgFallbackMatches[1]);
					} else {
						preg_match('/<img\s+[^>]*src=["\']([^"\']+)["\']/s', $divAvt, $imgFallbackMatches);
						if (!empty($imgFallbackMatches)) {
							$COVER = trim($imgFallbackMatches[1]);
						}
					}
				}
				$Link_URL = 'https://vietnamnet.vn' . trim($linkMatches[1]);
				if (isset($timeMatches[1])) {
					$DURATION = trim(str_replace("-", "", $timeMatches[1]));
				} else {
					$DURATION = "N/A";
				}
				$publish_time = trim($dateMatches[1]);
				$result["data"][] = [
					"title" => str_replace(["'", "\""], "", $title),
					"audio" => !empty($Link_URL) ? $Link_URL : "",
					"cover" => !empty($COVER) ? $COVER : "N/A",
					"duration" => !empty($DURATION) ? $DURATION : "N/A",
					"publish_time" => !empty($publish_time) ? $publish_time : "N/A",
					"source" => "Báo VietNamNet"
				];
				$printedTitles[] = $title;
			}
			if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
				$response['success'] = true;
				$response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
				$response['data'] = $result["data"];
			} else {
				$response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
			}
		} else {
			$response['message'] = "Không tìm thấy thẻ nào với class 'horizontalPost__avt' hoặc 'horizontalPost__main-actions'.\n";
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
	//Báo 24h.com.vn
	else if (strpos($News_Paper, "24h.com.vn") !== false) {
		$html = @file_get_contents($News_Paper);
		if ($html === false) {
			$response['message'] = "Không thể lấy dữ liệu từ báo: " . $News_Paper;
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}
		$html = preg_replace('/\s+/', ' ', $html);
		preg_match_all('/<article.*?>.*?<\/article>/i', $html, $articles);
		$result = ["data" => []];
		//Tối đa số lượng bài báo
		$maxItems = 30;
		$count = 0;
		foreach ($articles[0] as $article) {
			if ($count >= $maxItems) break;
			if (preg_match('/<figure.*?<a[^>]+href="([^"]+)"[^>]*>.*?<img[^>]+>/i', $article, $match)) {
				$href = html_entity_decode($match[1]);
				if (preg_match('/<img[^>]+data-original="([^"]+)"[^>]*alt="([^"]*)"[^>]*>/i', $article, $imgMatch)) {
					$dataOriginal = html_entity_decode($imgMatch[1]);
					$alt = html_entity_decode($imgMatch[2]);
					$alt = preg_replace('/[\'"<>\[\]\{\}\(\)\!\@\#\$\%\^\&\*\=\+\|\~`\/\\\\]/u', '', $alt);
					$alt = trim($alt);
				} else {
					$dataOriginal = '';
					$alt = '';
				}
				if ($href && $dataOriginal && strpos($dataOriginal, 'data:image') === false) {
					$result['data'][] = [
						'audio'  => $href,
						'cover'  => $dataOriginal,
						'title'  => $alt,
						'source' => 'Báo 24h.com.vn'
					];
					$count++;
				}
			}
		}
		if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
			$response = [
				'success' => true,
				'message' => "Dữ liệu đã được lưu vào tệp $filePath",
				'data'    => $result['data']
			];
		} else {
			$response = [
				'success' => false,
				'message' => "Lỗi: Không thể ghi dữ liệu vào tệp $filePath"
			];
		}
	}
	else {
		$response['message'] = 'Trang Báo chưa được hỗ trợ';
	}
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit();
}

// Kiểm tra dữ liệu POST với key zing_download_mp3_to_local
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Đọc dữ liệu JSON từ body (phải ở ngoài để xác định $postData)
	$input = file_get_contents('php://input');
	$postData = json_decode($input, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		echo json_encode([
			'success' => false,
			'message' => 'Lỗi phân tích JSON POST: ' . json_last_error_msg()
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	// Xử lý khi zing_download_mp3_to_local tồn tại và là mảng
	if (isset($postData['zing_download_mp3_to_local']) && is_array($postData['zing_download_mp3_to_local'])) {
		$response = ['success' => false, 'message' => 'Dữ liệu POST không hợp lệ hoặc thiếu zing_download_mp3_to_local.'];
		$data = $postData['zing_download_mp3_to_local'];
		$mp3Url = isset($data['url']) ? filter_var($data['url'], FILTER_SANITIZE_URL) : '';
		$songName = isset($data['name']) ? trim($data['name']) : '';
		// Kiểm tra dữ liệu đầu vào
		if (empty($mp3Url) || empty($songName) || !filter_var($mp3Url, FILTER_VALIDATE_URL)) {
			$response = [
				'success' => false,
				'message' => 'URL hoặc tên bài hát không hợp lệ.'
			];
		} else {
			// Đảm bảo tên file có đuôi .mp3
			if (!preg_match('/\.mp3$/i', $songName)) {
				$songName .= '.mp3';
			}
			// Thư mục đích để lưu file
			$savePath = $VBot_Offline . $Config['media_player']['music_local']['path'] . '/';
			$fullPath = $savePath . basename($songName);
			// Kiểm tra thư mục đích
			if (!is_dir($savePath)) {
				if (!mkdir($savePath, 0777, true)) {
					$response = [
						'success' => false,
						'message' => "Không thể tạo thư mục $savePath."
					];
				}
			} elseif (!is_writable($savePath)) {
				$response = [
					'success' => false,
					'message' => "Thư mục $savePath không có quyền ghi."
				];
			} else {
				$ch = curl_init($mp3Url);
				curl_setopt_array($ch, [
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_TIMEOUT => 90, // Tăng timeout cho file lớn
					CURLOPT_FAILONERROR => true,
					CURLOPT_SSL_VERIFYPEER => false, // Bỏ qua SSL nếu cần
					CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
				]);
				$fileContent = curl_exec($ch);
				if (curl_errno($ch)) {
					$response = [
						'success' => false,
						'message' => 'Lỗi cURL: ' . curl_error($ch)
					];
				} else {
					$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					if ($httpCode !== 200) {
						$response = [
							'success' => false,
							'message' => "Không thể tải file. Mã trạng thái HTTP: $httpCode"
						];
					} else {
						if (file_put_contents($fullPath, $fileContent) !== false) {
							chmod($fullPath, 0777);
							$response = [
								'success' => true,
								'message' => "Đã tải: " . basename($songName) . " vào thư mục nhạc Local"
							];
						} else {
							$response = [
								'success' => false,
								'message' => "Không thể lưu file tải xuống vào thư mục Local Kiểm tra quyền ghi."
							];
						}
					}
				}
				curl_close($ch);
			}
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	echo json_encode([
		'success' => false,
		'message' => 'Yêu cầu POST thiếu dữ liệu hoặc định dạng sai.'
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}
