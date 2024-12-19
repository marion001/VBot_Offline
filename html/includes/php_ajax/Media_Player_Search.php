<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php'; 
// Đặt tiêu đề để chỉ định nội dung là JSON
header('Content-Type: application/json');

// Lấy giao thức (http hoặc https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
// Lấy tên miền (ví dụ: 192.168.14.113)
//$domain = $_SERVER['HTTP_HOST'];
// Lấy đường dẫn tới file hiện tại (ví dụ: /html/includes/php_ajax/Media_Player_Search.php)
//$path = $_SERVER['REQUEST_URI'];
// Kết hợp thành URL đầy đủ
//$current_url = $protocol . $domain . $path;
// Sử dụng dirname để lùi lại 2 cấp thư mục (bỏ includes và php_ajax)
$Cover_URL_Local = dirname(dirname(dirname($Current_URL)));
// Kết quả sẽ là "http://192.168.14.113/html"
//echo $cover_url_local.'/assets/img/icon_audio_local.png';
//echo $Current_URL;

function formatDuration($duration) {
    // Nếu duration là số (chứa toàn bộ ký tự là số)
    if (ctype_digit($duration)) {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }
    return $duration; // Nếu đã là định dạng HH:mm:ss, trả về nguyên bản
}

// Hàm kiểm tra xem token podcast đã hết hạn chưa
function isTokenExpired_podcast($Config) {
    // Lấy thời gian hết hạn từ dữ liệu token
    $expire_time = $Config['media_player']['podcast']['expire_time'];
    // Kiểm tra xem thời gian hết hạn có hợp lệ không
    if (!is_numeric($expire_time)) {
        return false; // Nếu expire_time không phải là số hợp lệ, giả định là chưa hết hạn hoặc không có dữ liệu
    }
    // Lấy thời gian hiện tại
    $current_time = time();
    // So sánh thời gian hiện tại với thời gian hết hạn
    return $current_time > $expire_time;
}

// Hàm lấy lại token từ API
function refreshToken_podcast($Config, $VBot_Offline) {
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
      CURLOPT_POSTFIELDS =>'{"email":"'.base64_decode($Config['media_player']['podcast']['username']).'","password":"'.base64_decode($Config['media_player']['podcast']['password']).'"}',
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
if ($responseData['code'] === 200){
	// Lấy thời gian hiện tại tính bằng giây kể từ Epoch
	$currentTimestamp = time();
	$newTimestamp = $currentTimestamp + (12 * 3600);
	//echo $responseData['data']['access_token'];
	$Config['media_player']['podcast']['access_token'] = $responseData['data']['access_token'];
	$Config['media_player']['podcast']['expire_time'] = $newTimestamp;
	file_put_contents($VBot_Offline.'Config.json', json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
}

//Get danh sách các bài hát trong Local
if (isset($_GET['Local']))
{
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
    foreach ($items as $item)
    {
        if ($item === '.' || $item === '..')
        {
            continue;
        }
        $path = $directory . '/' . $item;
        if (is_file($path) && hasAllowedExtension($item, $allowed_extensions))
        {
            // Nếu là tệp và có phần mở rộng được phép
            $files_info[] = ['name' => $item, 'cover' => $Cover_URL_Local . '/assets/img/icon_audio_local.png', 'full_path' => $path, 'size' => bytesToMB(filesize($path)) ];
        }
    }
    // Trả về kết quả dưới dạng JSON
    //header('Content-Type: application/json');
    echo json_encode($files_info, JSON_PRETTY_PRINT);
    exit();
}

//Lấy Cookie ZingMP3
function getZmp3RqidCookie()
{
    // Khởi tạo cURL
    $curl = curl_init();
    // Cấu hình cURL để lấy cookie
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://zingmp3.vn/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true, // Để nhận cả header
        CURLOPT_NOBODY => false, // Để nhận nội dung của trang
        CURLOPT_COOKIEJAR => "", // Không lưu cookie vào tệp
        CURLOPT_COOKIEFILE => "", // Không đọc cookie từ tệp
        CURLOPT_TIMEOUT => 30,
    ));
    // Thực thi yêu cầu cURL
    $response = curl_exec($curl);
    // Kiểm tra lỗi cURL
    if (curl_errno($curl))
    {
        curl_close($curl);
        return ['error' => 'Lỗi cURL: ' . curl_error($curl) ];
    }
    // Đóng phiên cURL
    curl_close($curl);
    // Tìm giá trị của cookie zmp3_rqid trong header
    if (preg_match('/zmp3_rqid=([^;]+)/', $response, $matches))
    {
        return ['zmp3_rqid' => $matches[1]];
    }
    else
    {
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
        ) ,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    // Chuyển đổi phản hồi JSON thành mảng
    $response_data = json_decode($response, true);
    // Kiểm tra và xử lý kết quả
    if (isset($response_data['err']) && $response_data['err'] == 0 && isset($response_data['data']['128']))
    {
        $url_128 = $response_data['data']['128'];
        return ['success' => true, 'url' => $url_128];
    }
    else
    {
        return ['success' => false,
        //'error_message' => "Không lấy được link bài hát từ Zingmp3. Dữ liệu phản hồi: " . print_r($response_data, true)
        'error_message' => print_r($response_data['msg'], true) ];
    }

}

//Get các đài báo Radio
if (isset($_GET['Radio']))
{

    // Mảng chứa thông tin radio
    $radio_info = [];
    if ($Config['media_player']['radio_data'] && isset($Config['media_player']['radio_data']))
    {
        foreach ($Config['media_player']['radio_data'] as $radio)
        {
            $name = $radio['name'];
            $link = $radio['link'];
            // Thêm dữ liệu vào mảng $radio_info
            $radio_info[] = ['name' => $name, 'cover' => null, // Thay thế bằng URL ảnh bìa thực tế nếu có
            'full_path' => $link, 'size' => null
            // Nếu bạn có thông tin kích thước, có thể thay null bằng giá trị thực tế
            ];
        }
    }

    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($radio_info, JSON_PRETTY_PRINT);
    exit();

}

//Get link mp3 thành link player
if (isset($_GET['ZingMP3_GetLink']))
{
    $ZingMP3_id = isset($_GET['Zing_ID']) ? urlencode($_GET['Zing_ID']) : '';

    $result_cookieZINGMP3 = getZmp3RqidCookie();
    if (isset($result_cookieZINGMP3['error']))
    {
        echo json_encode(array(
            'success' => false,
            'error' => $result_cookieZINGMP3['error']
        ));
        exit();
    }
    else
    {
        $Link_Zing_mp3 = getLinkZingMP3($ZingMP3_id, $result_cookieZINGMP3['zmp3_rqid']);
        echo json_encode($Link_Zing_mp3);
    }
}

// tìm kiếm Zingmp3
if (isset($_GET['ZingMP3_Search']))
{
    $Song_Name = isset($_GET['SongName']) ? urlencode($_GET['SongName']) : '';
	
// Kiểm tra nếu biến Song_Name không có dữ liệu
if (empty($Song_Name)) {
    // Tạo mảng thông báo lỗi
    $response = array(
        'success' => false,
        'message' => 'Tên bài hát không được cung cấp.'
    );
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit; // Dừng thực hiện mã còn lại
}


    $zingJsonPath = '../cache/ZingMP3.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($zingJsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($zingJsonPath, json_encode([], JSON_PRETTY_PRINT));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($zingJsonPath, 0777);
    }

    // Khởi tạo cURL để tìm kiếm bài hát
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
    if (curl_errno($curl))
    {
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
    if ($data['result'] && isset($data['data'][0]['song']))
    {
        $songs = $data['data'][0]['song'];
        $results = array();
        foreach ($songs as $songData)
        {
            $ZingMP3_id = $songData['id'];
            $ZingMP3_name = $songData['name'];
            $ZingMP3_artist = $songData['artist'];
            $ZingMP3_duration = $songData['duration'];
            $ZingMP3_thumb = 'https://photo-zmp3.zmdcdn.me/' . $songData['thumb'];
            // Gọi hàm getLinkZingMP3 để lấy liên kết
            $results[] = array(
                'id' => $ZingMP3_id,
                //'name' => $ZingMP3_name,
                'name' => str_replace(["'", '"'], '', $ZingMP3_name ?? 'N/A'),
                'artist' => $ZingMP3_artist,
                'duration' => str_pad(floor($ZingMP3_duration / 60) , 2, '0', STR_PAD_LEFT) . ':' . str_pad($ZingMP3_duration % 60, 2, '0', STR_PAD_LEFT) ,
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
    }
    else
    {
        echo json_encode(array(
            'success' => false,
            'message' => 'Không tìm thấy bài hát.'
        ));
    }
    exit();
}

// get dữ liệu cache Zing
if (isset($_GET['Cache_ZingMP3'])) {
    // Đường dẫn tới file JSON
	$zingmp3JsonPath = "../cache/ZingMP3.json";
	
    if (!file_exists($zingmp3JsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($zingmp3JsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($zingmp3JsonPath, 0777);
    }
	
    // Đọc nội dung tệp JSON
    $jsonData = file_get_contents($zingmp3JsonPath);
    // Giải mã JSON thành mảng PHP
    $data = json_decode($jsonData, true);
    // Kiểm tra xem việc giải mã có thành công không
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mã hóa lại dữ liệu thành JSON và xuất ra
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Nếu giải mã không thành công, xuất lỗi
        echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    // Tạo mảng thông báo lỗi
    $response = array(
        'success' => false,
        'message' => 'Cần nhập dữ liệu để tìm kiếm',
		'data' => []
    );
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit; // Dừng thực hiện mã còn lại
}

    $podcastJsonPath = '../cache/PodCast.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($podcastJsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($podcastJsonPath, json_encode([], JSON_PRETTY_PRINT));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($podcastJsonPath, 0777);
    }

if (isTokenExpired_podcast($Config)) {
	// Nếu token đã hết hạn, làm mới token
    refreshToken_podcast($Config, $VBot_Offline);
}
//Tìm kiếm	
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
  CURLOPT_POSTFIELDS =>'{"operationName":"Search","query":"query Search($keyword: String!, $category: [String]!, $offset: Int!, $limit: Int!) {\\n  search(\\n    q: $keyword\\n    offset: $offset\\n    limit: $limit\\n    filter: {media: {types: $category}}\\n  ) {\\n    __typename\\n    episode {\\n      __typename\\n      audio\\n      duration\\n      id\\n      is_gcs\\n      published_at\\n      title\\n      media {\\n        __typename\\n        audio\\n        cover\\n        created_at\\n        id\\n        slug\\n        title\\n        total_episode\\n        content_type {\\n          __typename\\n          description\\n          value\\n        }\\n      }\\n      authors\\n      description\\n    }\\n    media {\\n      __typename\\n      cover\\n      created_at\\n      is_list\\n      slug\\n      title\\n      total_episode\\n      type\\n      id\\n      content_type {\\n        __typename\\n        description\\n        value\\n      }\\n    }\\n  }\\n}","variables":{"category":[],"keyword":"'.$podcast_Name.'","limit":'.$podcast_Limit.',"offset":0}}',
  CURLOPT_HTTPHEADER => array(
    'Host: core.ocs.iviet.com',
    'content-type: application/json',
    'accept: */*',
    'apollographql-client-version: 3.2.5-401',
    'authorization: Bearer '.$Config['media_player']['podcast']['access_token'],
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
                $audioUrl = $baseUrl .$audioUrl;
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

    // Cập nhật message nếu tìm thấy dữ liệu
    if ($result['success']) {
        $result['message'] = 'Dữ liệu được truy xuất thành công';
    }
	
	//Cache Ghi đè toàn bộ nội dung vào file ZingMP3.json
    file_put_contents($podcastJsonPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
echo json_encode($result, JSON_PRETTY_PRINT);
exit();
}

// get dữ liệu cache PodCast
if (isset($_GET['Cache_PodCast'])) {
    // Đường dẫn tới file JSON
    $podcastJsonPath = '../cache/PodCast.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($podcastJsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($podcastJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($podcastJsonPath, 0777);
    }
    // Đọc nội dung tệp JSON
    $jsonData = file_get_contents($podcastJsonPath);
    // Giải mã JSON thành mảng PHP
    $data = json_decode($jsonData, true);
    // Kiểm tra xem việc giải mã có thành công không
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mã hóa lại dữ liệu thành JSON và xuất ra
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Nếu giải mã không thành công, xuất lỗi
        echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
	exit();
}

//Tìm kiếm Youtube
if (isset($_GET['Youtube_Search'])) {
    $Youtube_Name = isset($_GET['Name']) ? $_GET['Name'] : '';
    $Youtube_Limit = isset($_GET['Limit']) ? $_GET['Limit'] : '20';

    // Kiểm tra nếu biến Youtube_Name không có dữ liệu
    if (empty($Youtube_Name)) {
        $responseb = array(
            'success' => false,
            'message' => 'Cần nhập dữ liệu để tìm kiếm',
            'data' => []
        );
        echo json_encode($responseb, JSON_PRETTY_PRINT);
        exit;
    }

    $youtubeJsonPath = '../cache/Youtube.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($youtubeJsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($youtubeJsonPath, json_encode([], JSON_PRETTY_PRINT));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($youtubeJsonPath, 0777);
    }


    $searchUrlYoutube = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($Youtube_Name) . "&maxResults=" . $Youtube_Limit . "&key=" . $Config['media_player']['youtube']['google_apis_key'];

    $curlYoutube = curl_init();
    curl_setopt_array($curlYoutube, array(
        CURLOPT_URL => $searchUrlYoutube,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $responseYoutube = curl_exec($curlYoutube);
    curl_close($curlYoutube);

//echo $responseYoutube;

    if ($responseYoutube === false) {
        echo json_encode(['error' => 'Yêu cầu cURL không thành công.']);
    } else {
        $dataYoutube = json_decode($responseYoutube, true);

        // Kiểm tra xem có lỗi từ API hoặc không có dữ liệu
        if (isset($dataYoutube['error'])) {
            echo json_encode(['error' => 'YouTube trả về lỗi: ' . $dataYoutube['error']['message']]);
        } elseif (empty($dataYoutube['items'])) {
            echo json_encode(['error' => 'Không có dữ liệu được tìm thấy.']);
        } else {
            $items = [];
            foreach ($dataYoutube['items'] as $itemYoutube) {
                $items[] = [
                    //'title' => $itemYoutube['snippet']['title'] ?? 'N/A',
                    'title' => str_replace(["'", '"'], '', $itemYoutube['snippet']['title'] ?? 'N/A'),
                    'id' => isset($itemYoutube['id']['videoId']) ? $itemYoutube['id']['videoId'] : 'N/A',
                    'channelTitle' => $itemYoutube['snippet']['channelTitle'] ?? 'N/A',
                    'link' => isset($itemYoutube['id']['videoId']) ? "https://www.youtube.com/watch?v=" . $itemYoutube['id']['videoId'] : '',
                    'cover' => $itemYoutube['snippet']['thumbnails']['high']['url'] ?? '',
                    'description' => $itemYoutube['snippet']['description'] ?? ''
                ];
            }

            $responsez = [
                'success' => true,
                'message' => 'Tìm kiếm thành công',
                'data' => $items
            ];
            echo json_encode($responsez, JSON_PRETTY_PRINT);
			
			file_put_contents($youtubeJsonPath, json_encode($responsez, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    exit();
}

if (isset($_GET['Cache_Youtube'])) {
    // Đường dẫn tới file JSON
    $youtubeJsonPath = '../cache/Youtube.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($youtubeJsonPath)) {
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($youtubeJsonPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($youtubeJsonPath, 0777);
    }
    // Đọc nội dung tệp JSON
    $jsonData = file_get_contents($youtubeJsonPath);
    // Giải mã JSON thành mảng PHP
    $data = json_decode($jsonData, true);
    // Kiểm tra xem việc giải mã có thành công không
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mã hóa lại dữ liệu thành JSON và xuất ra
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Nếu giải mã không thành công, xuất lỗi
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
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
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
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // Xác thực thông tin SSH
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response = array(
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        );
        echo json_encode($response, JSON_PRETTY_PRINT);
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
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // Chuyển sang chế độ đồng bộ để đợi kết quả
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);

    // Xử lý kết quả đầu ra
    if ($output) {
        $response = array(
            'success' => true,
            'message' => 'Lấy link thành công.',
            'data' => array(
                'dlink' => trim($output) // Loại bỏ khoảng trắng và ký tự thừa
            )
        );
    } else {
        $response = array(
            'success' => false,
            'message' => 'Không nhận được dữ liệu trả về từ Python script.',
            'data' => []
        );
    }

    // Đóng kết nối
    fclose($stream);

    // Trả về dữ liệu dạng JSON
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}


//Hiển thị dữ liệu playlist
if (isset($_GET['Cache_PlayList'])) {
    // Đường dẫn tới file JSON
    $playlistJsonPath = '../cache/PlayList.json';
    // Kiểm tra xem tệp có tồn tại không
    if (!file_exists($playlistJsonPath)) {
		
		        $initialData = [
            'success' => true,
            'message' => 'Danh sách phát tổng hợp',
            'data' => []
        ];
        // Nếu không tồn tại, tạo tệp mới với nội dung mặc định (mảng rỗng)
        file_put_contents($playlistJsonPath, json_encode($initialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // Thay đổi quyền truy cập của tệp thành 777
        chmod($playlistJsonPath, 0777);
    }
    // Đọc nội dung tệp JSON
    $jsonData = file_get_contents($playlistJsonPath);
    // Giải mã JSON thành mảng PHP
    $data = json_decode($jsonData, true);
    // Kiểm tra xem việc giải mã có thành công không
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mã hóa lại dữ liệu thành JSON và xuất ra
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Nếu giải mã không thành công, xuất lỗi
        echo json_encode(['success' => false, 'message' => 'Lỗi phân tích JSON.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
	exit();
}

// Thêm bài hát vào playlist
if (isset($_GET['playlist_ADD'])) {
    // Đường dẫn tới file JSON
    $filePath = '../cache/PlayList.json';
    // Khởi tạo phản hồi
    $response = [];
    // Kiểm tra nếu file chưa tồn tại
    if (!file_exists($filePath)) {
        // Tạo file mới với cấu trúc JSON mặc định
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
        // Kiểm tra và lấy dữ liệu từ POST request
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
        function generateRandomId($length = 6) {
            return strtoupper(bin2hex(random_bytes($length / 2)));
        }

        // Hàm để kiểm tra và chuyển đổi các giá trị "null" và rỗng thành null
        function convertToNull($value) {
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

        // Đọc nội dung hiện có của file JSON
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
            $response['message'] = ' Bài hát '.$_POST['title'].' đã tồn tại trong danh sách phát ở nguồn '.$_POST['source'];
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

    // Đường dẫn tới file JSON
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

            $updatedData = array_filter($updatedData, function($entry) use ($idsToDelete) {
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

        // Ghi dữ liệu JSON vào file với 'success' và 'message' giữ nguyên
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
            fclose($fileHandle); // Đóng file
            $response['success'] = true;
            $response['message'] = "Đã xóa cache $cacheType";
			echo json_encode($response);
        } else {
            $response['message'] = "Không thể mở file để ghi.";
				echo json_encode($response);
        }
    } else {
        $response['message'] = "File ".$cacheType.".json không tồn tại.";
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
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
	$filePath = "../cache/".$Config['media_player']['news_paper']['newspaper_file_name'];
    // Kiểm tra nếu chuỗi tồn tại trong biến
    if (strpos($News_Paper, "vnexpress.net") !== false) {
        // URL API
        $apiUrl = "https://api3.vnexpress.net/api/article?type=get_topstory&cate_id=1004685&site_id=1000000&showed_area=trangchu_podcast_v2&app_id=9e304d";
        // Khởi tạo cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Thực thi yêu cầu cURL
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
                            "title" => $podcast['title'],
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
    }

else if (strpos($News_Paper, "thanhnien.vn") !== false){
// Lấy nội dung HTML từ URL
$html = file_get_contents($News_Paper);
if ($html === false) {
    die("Không thể lấy dữ liệu từ URL.");
}
// Tìm và trích xuất JSON từ thẻ <script>
preg_match('/var\s+params\s*=\s*(\{.*?\});/is', $html, $matches);
if (!empty($matches[1])) {
    $params = json_decode($matches[1], true);
    if ($params && isset($params['jsonSkinAudio']['playList'])) {
        $playList = $params['jsonSkinAudio']['playList'];
        $result = ["data" => []];
        foreach ($playList as $item) {
                        $result["data"][] = [
                            "title" => $item['title'] ?? 'N/A',
                            "audio" => $item['jsonpost']['fullPost'][0]['link'] ?? 'N/A',
                            "cover" => str_replace('50_50', '150_150', $item['thumb'] ?? 'N/A'),
                            "duration" => "N/A",
							"publish_time" => "N/A",
                            "source" => "Báo Thanh Niên"
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
        echo "Không tìm thấy danh sách phát nhạc trong JSON.\n";
    }
} else {
    echo "Không tìm thấy JSON trong thẻ <script>.\n";
}
}
else if (strpos($News_Paper, "podcast.tuoitre.vn") !== false) {
    // Lấy nội dung HTML từ URL
    $html = file_get_contents($News_Paper);
    if ($html === false) {
        die("Không thể lấy dữ liệu từ URL.");
    }

    // Định nghĩa mẫu Regex để trích xuất các thuộc tính cần thiết
    $pattern = '/<a[^>]*data-file="([^"]+)"[^>]*data-title="([^"]+)"[^>]*data-avatar="([^"]+)"[^>]*>/';

    // Kết quả trích xuất
    $result = ["data" => []];
    
    // Mảng để lưu trữ các tiêu đề đã gặp
    $titles_seen = [];

    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // Lấy tiêu đề và giải mã
            $title = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
            
            // Kiểm tra nếu tiêu đề đã được gặp trước đó
            if (!in_array($title, $titles_seen)) {
                // Nếu chưa gặp, thêm vào mảng kết quả và đánh dấu tiêu đề này là đã gặp
                $result["data"][] = [
                    "title" => $title,
                    "audio" => $match[1],
                    "cover" => $match[3],
                    "duration" => "N/A",
                    "publish_time" => "N/A",
                    "source" => "Báo Tuổi Trẻ"
                ];
                $titles_seen[] = $title;  // Đánh dấu tiêu đề đã gặp
            }
        }

        // Lưu dữ liệu vào tệp JSON
        if (file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            $response['success'] = true;
            $response['message'] = "Dữ liệu đã được lưu vào tệp $filePath";
            $response['data'] = $result["data"];
        } else {
            $response['message'] = "Lỗi: Không thể ghi dữ liệu vào tệp $filePath";
        }
    } else {
        echo "Không tìm thấy dữ liệu phù hợp.\n";
    }
}

	else {
        $response['message'] = 'Trang Báo chưa được hỗ trợ';
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

?>
