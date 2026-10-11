<?php
include 'Configuration.php';
require_once __DIR__.'/includes/ActionRegistry.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($Config['contact_info']['user_login']['active']) {
  if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
    session_unset(); session_destroy(); header('Location: Login.php'); exit;
  }
}

$eventsFile = dirname(__DIR__).'/resource/calendar_events/events.json';
$eventsBackupDir = __DIR__.'/Backup_Upgrade/Backup_Events';
$eventsBackupLimit = 5;
$messages = [];
if (is_dir(dirname($eventsFile))) @chmod(dirname($eventsFile), 0777);
if (is_file($eventsFile)) @chmod($eventsFile, 0777);
$initialHistoryFile = dirname(__DIR__).'/resource/calendar_events/history.json';
if (is_file($initialHistoryFile)) @chmod($initialHistoryFile, 0777);
if (!is_dir($eventsBackupDir)) @mkdir($eventsBackupDir, 0777, true);
if (is_dir($eventsBackupDir)) @chmod($eventsBackupDir, 0777);
function calendarEventsRead($path) {
  $raw = is_file($path) ? file_get_contents($path) : false;
  $data = is_string($raw) ? json_decode($raw, true) : null;
  return is_array($data) && isset($data['events']) && is_array($data['events'])
    ? $data : ['schema_version'=>1, 'events'=>[]];
}
function calendarEventsText($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function calendarEventsHistoryKindLabel($value) {
  $labels=['manual_test'=>'Chạy thủ công','notification'=>'Thông báo','action'=>'Hành động'];
  $key=strtolower(trim((string)$value)); return $labels[$key]??(string)$value;
}
function calendarEventsHistoryStatusLabel($value) {
  $labels=['success'=>'Thành công','error'=>'Thất bại','failed'=>'Thất bại','cancelled'=>'Đã hủy','canceled'=>'Đã hủy'];
  $key=strtolower(trim((string)$value)); return $labels[$key]??(string)$value;
}
function calendarEventsIntegerList($value, $minimum, $maximum) {
  $items=is_array($value)?$value:preg_split('/[\s,;]+/',trim((string)$value)); $result=[];
  foreach($items as $item){if(filter_var($item,FILTER_VALIDATE_INT)!==false){$number=(int)$item;if($number>=$minimum&&$number<=$maximum)$result[$number]=$number;}}
  ksort($result); return array_values($result);
}
function calendarEventsTimeList($value) {
  $items=is_array($value)?$value:preg_split('/[\s,;]+/',trim((string)$value)); $result=[];
  foreach($items as $item){$item=trim((string)$item);if(preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$item))$result[$item]=$item;}
  ksort($result); return array_values($result);
}
function calendarEventsValidMediaUrl($value) {
  $url=trim((string)$value);
  if($url===''||strlen($url)>2048||filter_var($url,FILTER_VALIDATE_URL)===false)return false;
  $parts=parse_url($url);
  return is_array($parts)&&in_array(strtolower((string)($parts['scheme']??'')),['http','https'],true)
    &&!empty($parts['host'])&&!isset($parts['user'])&&!isset($parts['pass']);
}
function calendarEventsValidLocalPath($value) {
  return is_string($value)&&trim($value)!==''&&trim($value)===$value&&strlen($value)<=2048
    &&strpos($value,"\0")===false&&strpos($value,'://')===false;
}
function calendarEventsGenerateId($name, $events) {
  $value=trim((string)$name); $value=function_exists('mb_strtolower')?mb_strtolower($value,'UTF-8'):strtolower($value);
  $value=strtr($value,['đ'=>'d','Đ'=>'d']);
  $ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value); if(!is_string($ascii))$ascii=$value;
  $base=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower($ascii)),'-');
  if($base==='')$base='event'; $base=substr($base,0,70);
  $used=[]; foreach($events as $event)$used[strtolower((string)($event['id']??''))]=true;
  $candidate=$base; $number=2;
  while(isset($used[strtolower($candidate)])){$suffix='-'.$number++;$candidate=substr($base,0,80-strlen($suffix)).$suffix;}
  return $candidate;
}
function calendarEventsRedirect($status) {
  $_SESSION['calendar_events_status']=(string)$status;
  header('Location: Calendar_Events.php'); exit;
}
function calendarEventsBackup($source, $directory, $limit=5) {
  if (!is_file($source) || !is_dir($directory)) return true;
  $backup = $directory.'/Events_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(3)), 0, 6).'.json';
  $content = file_get_contents($source);
  if (!is_string($content) || !vbotAtomicWriteFile($backup, $content, 'calendar events backup')) return false;
  @chmod($backup, 0777);
  $files = glob($directory.'/Events_*.json') ?: [];
  usort($files, fn($a,$b)=>(filemtime($b)?:0)<=>(filemtime($a)?:0));
  foreach (array_slice($files, max(1,(int)$limit)) as $old) @unlink($old);
  return true;
}
function calendarEventsValidBundle($data) {
  if (!is_array($data) || (int)($data['schema_version']??1)!==1 || !isset($data['events']) || !is_array($data['events']) || count($data['events']) > 512) return false;
  $ids=[];
  foreach ($data['events'] as $event) {
    if (!is_array($event)) return false;
    $id=trim((string)($event['id']??'')); $name=trim((string)($event['name']??''));
    $calendar=(string)($event['calendar']??'solar'); $eventCalendar=(string)($event['event_calendar']??$calendar);
    $months=calendarEventsIntegerList($event['months']??[$event['month']??0],1,12); $days=calendarEventsIntegerList($event['days']??[$event['day']??0],1,31);
    $year=$event['year']??null; $times=calendarEventsTimeList($event['times']??[$event['time']??($event['notification']['time']??'08:00')]);
    $eventMonth=$event['event_month']??null; $eventDay=$event['event_day']??null;
    $recurrence=(string)($event['recurrence']??($year===null?'yearly':'once'));
    $execution=is_array($event['execution']??null)?$event['execution']:[];
    $executionAction=(string)($execution['action']??'none');
    if($executionAction==='media_url'&&!calendarEventsValidMediaUrl($execution['media_url']??''))return false;
    if($executionAction==='media_local'&&!calendarEventsValidLocalPath($execution['media_path']??''))return false;
    $validationYear=$recurrence==='once'?($year?:2000):2000;
    $hasValidSolar=false; foreach($months as $month)foreach($days as $day)if(checkdate($month,$day,$validationYear))$hasValidSolar=true;
    $hasValidEventDate=$eventMonth===null||$eventDay===null||$eventCalendar==='lunar'||checkdate($eventMonth,$eventDay,$year?:2000);
    if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/',$id)||$name===''||isset($ids[strtolower($id)])||!in_array($calendar,['solar','lunar'],true)||!in_array($eventCalendar,['solar','lunar'],true)
        ||!$months||!$days||!$times||($calendar==='lunar'&&max($days)>30)||($calendar==='solar'&&!$hasValidSolar)
        ||($year!==null&&(!is_int($year)||$year<1900||$year>9999))
        ||($eventMonth!==null&&(!is_int($eventMonth)||$eventMonth<1||$eventMonth>12))
        ||($eventDay!==null&&(!is_int($eventDay)||$eventDay<1||$eventDay>($eventCalendar==='lunar'?30:31)))||!$hasValidEventDate||!in_array($recurrence,['once','yearly'],true)
        ||($recurrence==='once'&&$year===null)) return false;
    $ids[strtolower($id)]=true;
  }
  return true;
}
function calendarEventsWrite($path, $data, $backupDir=null, $backupLimit=5) {
  if (!calendarEventsValidBundle($data)) return false;
  $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($encoded === false) return false;
  if ($backupDir !== null && !calendarEventsBackup($path, $backupDir, $backupLimit)) return false;
  $written=vbotAtomicWriteFile($path, $encoded."\n", 'calendar events');
  if($written)@chmod($path,0777);
  return $written;
}

$eventsData = calendarEventsRead($eventsFile);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_operation'])) {
  $operation = (string)$_POST['event_operation'];
  if ($operation === 'simulate_query') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $query = trim((string)($_POST['query'] ?? ''));
    $at = trim((string)($_POST['simulate_at'] ?? ''));
    $locale = str_replace('_', '-', trim((string)($_POST['locale'] ?? ($Config['language']['primary'] ?? 'vi-VN'))));
    $languageFile = dirname(__DIR__).'/resource/lang_keywords/'.$locale.'.json';
    $languageData = is_file($languageFile) ? json_decode((string)file_get_contents($languageFile), true) : null;
    $validLocaleFile = is_array($languageData) && strcasecmp((string)($languageData['locale'] ?? ''), $locale) === 0;
    if ($query === '' || strlen($query) > 500 || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $at) !== 1 || preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})?$/', $locale) !== 1 || !$validLocaleFile) {
      http_response_code(400); echo json_encode(['success'=>false,'error'=>'Câu hỏi mô phỏng không hợp lệ'], JSON_UNESCAPED_UNICODE); exit;
    }
    $python = is_executable('/usr/bin/python3') ? '/usr/bin/python3' : 'python3';
    $command = escapeshellcmd($python).' '.escapeshellarg(__DIR__.'/includes/php_ajax/Calendar_Events_CLI.py').' --query-base64 '.escapeshellarg(base64_encode($query)).' --locale '.escapeshellarg($locale).' --at '.escapeshellarg($at).' 2>&1';
    $output = shell_exec($command); $result = is_string($output) ? json_decode(trim($output), true) : null;
    if (!is_array($result)) { http_response_code(500); $result=['success'=>false,'error'=>'Không thể chạy bộ thử câu hỏi Events']; }
    else {
      $eventLanguageSettings=$languageData['values']['calendar_events']??null;
      $requiredLanguageKeys=[
        'countdown_terms','birthday_terms','month_terms','upcoming_terms','day_number_prefixes','month_number_prefixes',
        'year_number_prefixes','month_names','date_order','allow_bare_year','explicit_date_label',
        'week_queries','range_start_terms','range_end_terms','next_days_prefixes','next_days_suffixes',
        'month_label','month_number_label','upcoming_label','range_label','next_days_label','date_format'
      ];
      $result['language_settings_complete']=is_array($eventLanguageSettings)&&count(array_diff($requiredLanguageKeys,array_keys($eventLanguageSettings)))===0;
      $result['events_active']=!empty($Config['calendar']['events']['active']);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
  }
  if ($operation === 'simulate') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $index = filter_var($_POST['event_index'] ?? null, FILTER_VALIDATE_INT);
    $at = trim((string)($_POST['simulate_at'] ?? ''));
    if ($index === false || !isset($eventsData['events'][$index]) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $at) !== 1) {
      http_response_code(400); echo json_encode(['success'=>false,'error'=>'Dữ liệu mô phỏng không hợp lệ'], JSON_UNESCAPED_UNICODE); exit;
    }
    $payload = base64_encode(json_encode($eventsData['events'][$index], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $python = is_executable('/usr/bin/python3') ? '/usr/bin/python3' : 'python3';
    $command = escapeshellcmd($python).' '.escapeshellarg(__DIR__.'/includes/php_ajax/Calendar_Events_CLI.py').' --simulate-base64 '.escapeshellarg($payload).' --at '.escapeshellarg($at).' 2>&1';
    $output = shell_exec($command);
    $result = is_string($output) ? json_decode(trim($output), true) : null;
    if (!is_array($result)) { http_response_code(500); $result=['success'=>false,'error'=>'Không thể chạy bộ mô phỏng Python']; }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
  }
  if ($operation === 'clear_history') {
    $historyFile = dirname(__DIR__).'/resource/calendar_events/history.json';
    $cleared=vbotAtomicWriteFile($historyFile, "[]\n", 'calendar events history'); if($cleared)@chmod($historyFile,0777);
    calendarEventsRedirect($cleared ? 'history_cleared' : 'write_error');
  }
  if ($operation === 'import') {
    if (!isset($_FILES['events_import_file']) || $_FILES['events_import_file']['error'] !== UPLOAD_ERR_OK || $_FILES['events_import_file']['size'] > 524288) calendarEventsRedirect('import_invalid');
    $importRaw = file_get_contents($_FILES['events_import_file']['tmp_name']);
    $importData = is_string($importRaw) ? json_decode($importRaw, true) : null;
    if (!calendarEventsValidBundle($importData)) calendarEventsRedirect('import_invalid');
    if (($_POST['import_mode'] ?? 'overwrite') === 'merge') {
      $merged=[];
      foreach ($eventsData['events'] as $event) $merged[strtolower((string)$event['id'])]=$event;
      foreach ($importData['events'] as $event) $merged[strtolower((string)$event['id'])]=$event;
      $eventsData['events']=array_values($merged);
      if (count($eventsData['events']) > 512) calendarEventsRedirect('import_invalid');
    } else $eventsData=$importData;
    calendarEventsRedirect(calendarEventsWrite($eventsFile,$eventsData,$eventsBackupDir,$eventsBackupLimit)?'imported':'write_error');
  }
  if ($operation === 'restore_backup') {
    $name=basename((string)($_POST['backup_file']??'')); $path=$eventsBackupDir.'/'.$name;
    if (!preg_match('/^Events_[A-Za-z0-9_-]+\.json$/',$name)||!is_file($path)) calendarEventsRedirect('invalid');
    $restore=json_decode((string)file_get_contents($path),true);
    if (!calendarEventsValidBundle($restore)) calendarEventsRedirect('import_invalid');
    calendarEventsRedirect(calendarEventsWrite($eventsFile,$restore,$eventsBackupDir,$eventsBackupLimit)?'restored':'write_error');
  }
  if ($operation === 'delete_backup') {
    $name=basename((string)($_POST['backup_file']??'')); $path=$eventsBackupDir.'/'.$name;
    if (!preg_match('/^Events_[A-Za-z0-9_-]+\.json$/',$name)||!is_file($path)) calendarEventsRedirect('invalid');
    calendarEventsRedirect(@unlink($path)?'backup_deleted':'write_error');
  }
  if ($operation === 'delete') {
    $index = filter_var($_POST['event_index'] ?? null, FILTER_VALIDATE_INT);
    $expectedId = (string)($_POST['event_id'] ?? '');
    if ($index === false || !isset($eventsData['events'][$index]) || !hash_equals((string)($eventsData['events'][$index]['id'] ?? ''), $expectedId)) {
      calendarEventsRedirect('invalid');
    }
    array_splice($eventsData['events'], $index, 1);
    calendarEventsRedirect(calendarEventsWrite($eventsFile, $eventsData, $eventsBackupDir, $eventsBackupLimit) ? 'deleted' : 'write_error');
  }
  if ($operation === 'save') {
    $indexRaw = $_POST['event_index'] ?? '';
    $index = $indexRaw === '' ? null : filter_var($indexRaw, FILTER_VALIDATE_INT);
    $name = trim((string)($_POST['event_name'] ?? ''));
    if($index===null)$id=calendarEventsGenerateId($name,$eventsData['events']);
    elseif($index!==false&&isset($eventsData['events'][$index]))$id=(string)($eventsData['events'][$index]['id']??'');
    else calendarEventsRedirect('invalid');
    $eventCalendar = (string)($_POST['event_calendar'] ?? 'solar');
    $calendar = (string)($_POST['schedule_calendar'] ?? 'solar');
    $yearRaw = trim((string)($_POST['event_year'] ?? ''));
    $year = $yearRaw === '' ? null : filter_var($yearRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1900,'max_range'=>9999]]);
    $eventMonthRaw=trim((string)($_POST['event_month']??'')); $eventDayRaw=trim((string)($_POST['event_day']??''));
    $eventMonth=$eventMonthRaw===''?null:filter_var($eventMonthRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>12]]);
    $eventDay=$eventDayRaw===''?null:filter_var($eventDayRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>31]]);
    $months = calendarEventsIntegerList($_POST['event_months'] ?? '', 1, 12);
    $days = calendarEventsIntegerList($_POST['event_days'] ?? '', 1, 31);
    $times = calendarEventsTimeList($_POST['event_times'] ?? '');
    $recurrence = (string)($_POST['event_recurrence'] ?? 'yearly');
    $beforeDays = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', (string)($_POST['event_before_days'] ?? '0'))), fn($value)=>$value >= 0 && $value <= 365)));
    rsort($beforeDays);
    $actionRaw=trim((string)($_POST['event_action']??'none'));
    $action=in_array($actionRaw,['media_url','media_local'],true)?$actionRaw:vbotActionRegistryNormalize($Config,$actionRaw);
    $mediaUrl=trim((string)($_POST['event_media_url']??''));
    $mediaPath=$_POST['event_media_path']??'';
    $validDate = $calendar === 'lunar' ? ($days && max($days) <= 30) : false;
    if($calendar==='solar')foreach($months as $month)foreach($days as $day)if(checkdate($month,$day,$recurrence==='once'?($year?:2000):2000))$validDate=true;
    $validEventDate=$eventMonth===null||$eventDay===null||$eventCalendar==='lunar'||checkdate($eventMonth,$eventDay,$year?:2000);
    $duplicate = false;
    $duplicateMoment = false;
    foreach ($eventsData['events'] as $position=>$existing) {
      if ($position !== $index && strcasecmp((string)($existing['id'] ?? ''), $id) === 0) $duplicate = true;
      $existingMonths=calendarEventsIntegerList($existing['months']??[$existing['month']??0],1,12); $existingDays=calendarEventsIntegerList($existing['days']??[$existing['day']??0],1,31);
      $existingTimes=calendarEventsTimeList($existing['times']??[$existing['time']??($existing['notification']['time']??'08:00')]);
      if ($position !== $index && ($existing['calendar'] ?? 'solar') === $calendar
          && ($existing['year'] ?? null) === $year && array_intersect($existingMonths,$months)
          && array_intersect($existingDays,$days) && array_intersect($existingTimes,$times)) $duplicateMoment = true;
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $id) || $name === '' || strlen($name) > 200 || !in_array($calendar, ['solar','lunar'], true)
        || ($yearRaw !== '' && $year === false) || ($eventMonthRaw!==''&&$eventMonth===false) || ($eventDayRaw!==''&&$eventDay===false)
        || !in_array($eventCalendar,['solar','lunar'],true) || ($eventCalendar==='lunar'&&$eventDay!==null&&$eventDay>30) || !$validEventDate
        || !$months || !$days || !$times || !$validDate || !in_array($recurrence, ['once','yearly'], true)
        || $duplicate || !$beforeDays
        || ($action === 'media_url' && !calendarEventsValidMediaUrl($mediaUrl))
        || ($action === 'media_local' && !calendarEventsValidLocalPath($mediaPath))
        || ($recurrence === 'once' && $year === null)) {
      calendarEventsRedirect('invalid');
    }
    if ($duplicateMoment) calendarEventsRedirect('duplicate_moment');
    $event = [
      'id'=>$id, 'name'=>$name, 'active'=>isset($_POST['event_active']), 'event_calendar'=>$eventCalendar, 'calendar'=>$calendar,
      'year'=>$year, 'event_month'=>$eventMonth, 'event_day'=>$eventDay, 'months'=>$months, 'days'=>$days,
      'leap_month'=>$calendar === 'lunar' ? (($_POST['event_leap_month'] ?? '') === '' ? null : ($_POST['event_leap_month'] === 'true')) : null,
      'recurrence'=>$recurrence, 'times'=>$times,
      'tags'=>array_slice(array_values(array_unique(array_map(fn($tag)=>substr($tag, 0, 60), array_filter(array_map('trim', preg_split('/\R/u', (string)($_POST['event_tags'] ?? ''))), fn($tag)=>$tag !== '')))), 0, 20),
      'description'=>substr(trim((string)($_POST['event_description'] ?? '')), 0, 1000),
      'notification'=>[
        'active'=>isset($_POST['notification_active']), 'before_days'=>$beforeDays,
        'repeat'=>max(1,min(5,(int)($_POST['notification_repeat'] ?? 1))),
        'message'=>substr(trim((string)($_POST['notification_message'] ?? '')), 0, 1000),
      ],
      'execution'=>['active'=>$action !== 'none', 'action'=>$action, 'media_url'=>$action==='media_url'?$mediaUrl:'', 'media_path'=>$action==='media_local'?$mediaPath:''],
    ];
    if ($index === null) $eventsData['events'][] = $event;
    elseif ($index !== false && isset($eventsData['events'][$index])) $eventsData['events'][$index] = $event;
    else calendarEventsRedirect('invalid');
    calendarEventsRedirect(calendarEventsWrite($eventsFile, $eventsData, $eventsBackupDir, $eventsBackupLimit) ? 'saved' : 'write_error');
  }
}

$editIndex = null;
$editing = null;
$form = ['id'=>'','name'=>'','active'=>true,'event_calendar'=>'solar','calendar'=>'solar','year'=>null,'months'=>[1],'days'=>[1],'leap_month'=>false,'recurrence'=>'yearly','times'=>['08:00'],'tags'=>[],'description'=>'','notification'=>['active'=>true,'before_days'=>[0],'repeat'=>1,'message'=>''],'execution'=>['active'=>false,'action'=>'none','media_url'=>'']];
$statusMessages = ['saved'=>'Đã lưu sự kiện. Cần Restart chương trình VBot để nạp dữ liệu mới.','deleted'=>'Đã xóa sự kiện. Cần Restart chương trình VBot để áp dụng.','imported'=>'Đã nhập sự kiện và tạo bản sao lưu. Restart chương trình VBot để áp dụng.','restored'=>'Đã khôi phục sự kiện và sao lưu dữ liệu trước khi khôi phục.','backup_deleted'=>'Đã xóa file sao lưu sự kiện.','import_invalid'=>'File nhập/khôi phục không đúng schema sự kiện hoặc vượt quá 512 mục.','history_cleared'=>'Đã xóa lịch sử Events.','duplicate_moment'=>'Đã có sự kiện khác trùng loại lịch, ngày và giờ kích hoạt.','invalid'=>'Dữ liệu không hợp lệ: hãy kiểm tra ngày, tháng, giờ, kiểu lặp và năm. sự kiện chạy một lần bắt buộc có năm.','write_error'=>'Không thể ghi dữ liệu sự kiện hoặc tạo backup.'];
$pageStatus=(string)($_SESSION['calendar_events_status']??''); unset($_SESSION['calendar_events_status']);
$historyFile = dirname(__DIR__).'/resource/calendar_events/history.json';
$historyRaw = is_file($historyFile) && is_readable($historyFile) ? json_decode((string)@file_get_contents($historyFile), true) : [];
$history = is_array($historyRaw) ? array_reverse(array_slice($historyRaw, -200)) : [];
$backupFiles=glob($eventsBackupDir.'/Events_*.json')?:[];
usort($backupFiles,fn($a,$b)=>(filemtime($b)?:0)<=>(filemtime($a)?:0));
$eventLanguageOptions=[];
foreach (glob(dirname(__DIR__).'/resource/lang_keywords/*.json') ?: [] as $languagePath) {
  if (substr($languagePath, -13) === '.example.json') continue;
  $languageData=json_decode((string)file_get_contents($languagePath),true);
  $locale=is_array($languageData)?str_replace('_','-',trim((string)($languageData['locale']??''))):'';
  if ($locale!=='' && preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})?$/',$locale)===1 && basename($languagePath)===$locale.'.json') {
    $eventLanguageOptions[$locale]=trim((string)($languageData['name']??$locale));
  }
}
ksort($eventLanguageOptions);
$activeEventLocale=str_replace('_','-',(string)($Config['language']['primary']??'vi-VN'));
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; ?>
<head>
 <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>">
</head>
<body><?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <p class="small text-muted">Ngưỡng tìm tên/Tags trong câu hỏi về sự kiện: <?= number_format((float)($Config['calendar']['events']['minimum_threshold']??0.90),2) ?>. Chỉnh tại <a href="Config.php#accordion_button_calendar_events">Cấu hình Events</a>; không thay đổi ngày, giờ tự chạy.</p>
  <div class="pagetitle"><h1>Quản Lý Sự Kiện, Events, Ngày Lễ, Kỉ Niệm</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Events</li>&nbsp;| Trạng Thái Kích Hoạt: <?php echo ($Config['calendar']['events']['active'] ?? false) ? '<p class="text-success">&nbsp; Đang Bật</p>' : '<p class="text-danger">&nbsp; Đang Tắt</p>'; ?></ol></nav></div>
  <?php if (isset($statusMessages[$pageStatus])): ?><div class="alert alert-<?php echo in_array($pageStatus,['saved','deleted','imported','restored','backup_deleted','history_cleared'],true)?'success':'danger'; ?>"><?php echo calendarEventsText($statusMessages[$pageStatus]); ?></div><?php endif; ?>

  <div class="card alert alert-success"><div class="card-body pt-3">
    <button type="button" class="btn btn-success" id="show_event_form_button"><i class="bi bi-calendar-plus"></i> Tạo Mới Sự Kiện, Event</button>
    <div id="calendar_event_form_container" class="d-none mt-3"><h5 class="card-title" id="calendar_event_form_title">Thêm Mới Sự Kiện</h5>
    <form method="post" class="row g-3" id="calendar-event-form" onsubmit="return validateCalendarEventForm()">
      <input type="hidden" name="event_operation" value="save"><input type="hidden" name="event_index" value=""><input type="hidden" name="event_id" value="">
      <div class="col-12"><label class="form-label">Tên sự kiện</label><input required placeholder="Nhập tên sự kiện" maxlength="200" class="form-control border-success" name="event_name" value=""></div>
      <div class="col-12"><h6 class="text-primary border-bottom pb-2 mb-0"><i class="bi bi-calendar-date"></i> Ngày, tháng, năm bắt đầu (có, xảy ra) sự kiện</h6></div>
      <div class="col-md-3"><label class="form-label">Loại lịch của sự kiện</label><select class="form-select border-success" name="event_calendar"><option value="solar" <?php echo $form['event_calendar']==='solar'?'selected':''; ?>>Lịch Dương</option><option value="lunar" <?php echo $form['event_calendar']==='lunar'?'selected':''; ?>>Lịch Âm</option></select><div class="form-text text-danger">Xác định ngày, tháng, năm gốc của sự kiện thuộc lịch nào.</div></div>
      <div class="col-md-3"><label class="form-label">Ngày (có, xảy ra) sự kiện</label><input type="number" min="1" max="31" class="form-control border-success" name="event_day" value="" placeholder="1-31"><div class="form-text text-danger">Có thể bỏ trống</div></div>
      <div class="col-md-3"><label class="form-label">Tháng (có, xảy ra) sự kiện</label><input type="number" min="1" max="12" class="form-control border-success" name="event_month" value="" placeholder="1-12"><div class="form-text text-danger">Có thể bỏ trống</div></div>
      <div class="col-md-3"><label class="form-label">Năm (có, xảy ra) sự kiện</label><input type="number" min="1900" max="9999" class="form-control border-success" name="event_year" value=""><div class="form-text text-danger">Có thể bỏ trống (Nhập nếu là ngày Sinh, ngày Mất, V..v...)</div></div>
      <div class="col-12"><h6 class="text-primary border-bottom pb-2 mb-0"><i class="bi bi-gear"></i> Cấu hình chạy sự kiện</h6></div>
      <div class="col-md-3"><label class="form-label">Loại lịch chạy</label><select class="form-select border-success" name="schedule_calendar"><option value="solar" <?php echo $form['calendar']==='solar'?'selected':''; ?>>Lịch Dương</option><option value="lunar" <?php echo $form['calendar']==='lunar'?'selected':''; ?>>Lịch Âm</option></select><div class="form-text text-danger">VBot dùng loại lịch này để xác định các tháng và ngày chạy.</div></div>
 <div class="col-md-3"><label class="form-label">Các ngày chạy</label><div id="event_days_list"></div><button type="button" class="btn btn-sm btn-success w-100" onclick="addEventScheduleValue('days')"><i class="bi bi-plus-circle"></i> Thêm ngày</button><div class="form-text text-danger">Mỗi ngày áp dụng cho tất cả tháng.</div></div>     
	 <div class="col-md-3"><label class="form-label">Các tháng chạy</label><div id="event_months_list"></div><button type="button" class="btn btn-sm btn-success w-100" onclick="addEventScheduleValue('months')"><i class="bi bi-plus-circle"></i> Thêm tháng</button></div>
     
      <div class="col-md-3"><label class="form-label">Các giờ chạy</label><div id="event_times_list"></div><button type="button" class="btn btn-sm btn-success w-100" onclick="addEventScheduleValue('times')"><i class="bi bi-plus-circle"></i> Thêm giờ</button><div class="form-text text-danger">Định dạng 24 giờ.</div></div>
      <div class="col-md-3"><label class="form-label">Lặp lại</label><select class="form-select border-success" name="event_recurrence"><option value="yearly" <?php echo $form['recurrence']==='yearly'?'selected':''; ?>>Hằng năm</option><option value="once" <?php echo $form['recurrence']==='once'?'selected':''; ?>>Một lần</option></select></div>
      <div class="col-md-3"><label class="form-label">Tháng âm nhuận</label><select class="form-select border-success" name="event_leap_month"><option value="" <?php echo ($form['leap_month']??null)===null?'selected':''; ?>>Không phân biệt</option><option value="false" <?php echo ($form['leap_month']??null)===false?'selected':''; ?>>Tháng thường</option><option value="true" <?php echo ($form['leap_month']??null)===true?'selected':''; ?>>Tháng nhuận</option></select></div>
      <div class="col-md-3"><label class="form-label">Báo trước (ngày)</label><input class="form-control border-success" name="event_before_days" value="<?php echo calendarEventsText(implode(',', $form['notification']['before_days'] ?? [0])); ?>" placeholder="7,3,1,0"><div class="form-text text-danger">Nhập 0 nếu không cần báo trước ngày</div></div>
      <div class="col-md-3"><label class="form-label">Số lần lặp lại thông báo</label><input type="number" min="1" max="5" class="form-control border-success" name="notification_repeat" value="<?php echo (int)($form['notification']['repeat'] ?? 1); ?>"></div>
      <div class="col-12 border border-info rounded p-3">
        <label class="form-label fw-bold text-primary" for="notification_message"><i class="bi bi-volume-up"></i> Nội dung VBot đọc phát thông báo ra loa</label>
        <textarea maxlength="1000" rows="3" class="form-control border-success" name="notification_message" id="notification_message" placeholder="Ví dụ: Hôm nay là sinh nhật mẹ, bạn nhớ chuẩn bị quà nhé."><?php echo calendarEventsText($form['notification']['message'] ?? ''); ?></textarea>
        <div class="form-text">Trường JSON: <code>notification.message</code>. Nếu để trống, VBot tự tạo câu thông báo từ tên Event và số ngày báo trước.</div>
      </div>
      <div class="col-md-6"><label class="form-label">Hành động VBot thực hiện khi đến lịch</label><select class="form-select border-success" name="event_action" id="event_action"><?php $selectedEventAction=$form['execution']['action']??'none'; echo vbotActionRegistryRender($Config,in_array($selectedEventAction,['media_url','media_local'],true)?'none':$selectedEventAction); ?><option value="media_url" <?php echo $selectedEventAction==='media_url'?'selected':''; ?>>Phát media từ Link/URL</option><option value="media_local" <?php echo $selectedEventAction==='media_local'?'selected':''; ?>>Phát một bài nhạc Local cụ thể</option></select></div>
      <div class="col-md-6 d-none" id="event_media_url_group"><label class="form-label" for="event_media_url">Link/URL âm thanh hoặc media</label><input type="url" maxlength="2048" class="form-control border-success" name="event_media_url" id="event_media_url" value="<?php echo calendarEventsText($form['execution']['media_url']??''); ?>" placeholder="https://example.com/audio.mp3"><div class="form-text">Hỗ trợ link stream, YouTube, ZingMP3, NhacCuaTui và file âm thanh HTTP/HTTPS như MP3, WAV, FLAC...</div></div>
      <div class="col-md-6 d-none" id="event_media_local_group">
        <label class="form-label" for="event_media_path">Bài nhạc Local cụ thể</label>
        <div class="input-group mb-2"><input type="search" class="form-control border-success" id="event_local_search" placeholder="Tìm theo tên bài nhạc" aria-label="Tìm bài nhạc Local"><button type="button" class="btn btn-outline-primary" id="event_local_refresh">Tải lại danh sách nhạc</button></div>
        <select class="form-select border-success" name="event_media_path" id="event_media_path"><option value="">Chọn bài nhạc Local…</option><?php if(!empty($form['execution']['media_path'])): ?><option selected value="<?php echo calendarEventsText($form['execution']['media_path']); ?>"><?php echo calendarEventsText(basename($form['execution']['media_path'])); ?></option><?php endif; ?></select>
        <div class="form-text" id="event_local_status">Danh sách lấy từ thư mục nhạc Local đã cấu hình, gồm cả thư mục con.</div>
      </div>
      <div class="col-md-6"><label class="form-label" for="event_tags">Tags (mỗi dòng một tag)</label><textarea class="form-control border-success" name="event_tags" id="event_tags" rows="4" placeholder="ngày lễ&#10;gia đình&#10;sinh nhật"><?php echo calendarEventsText(implode("\n", $form['tags'] ?? [])); ?></textarea><div class="form-text">Tags là các từ khóa phụ giúp VBot tìm và nhóm Event khi người dùng hỏi bằng giọng nói. Ví dụ Event “Sinh nhật mẹ” có thể thêm các tag: <code>gia đình</code>, <code>sinh nhật</code>, <code>mẹ</code>. Nhập mỗi tag trên một dòng; Tags không được đọc qua loa.</div></div>
      <div class="col-12"><label class="form-label" for="event_description">Mô tả/Ghi chú nội bộ</label><textarea maxlength="1000" class="form-control border-success" name="event_description" id="event_description"><?php echo calendarEventsText($form['description'] ?? ''); ?></textarea><div class="form-text">Nội dung này chỉ dùng để ghi chú, VBot không đọc ra loa.</div></div>
      <div class="col-12 d-flex flex-wrap gap-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="event_active" id="event_active" checked><label class="form-check-label" for="event_active">Kích Hoạt</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="notification_active" id="notification_active" checked><label class="form-check-label" for="notification_active">Đọc thông báo ra loa</label></div></div>
      
<div class="col-12 d-flex justify-content-center gap-2 flex-wrap">
    <button class="btn btn-success" type="submit">
        <i class="bi bi-save"></i> Lưu Sự Kiện, Event
    </button>

    <button type="button" class="btn btn-secondary" id="cancel_event_edit">
        <i class="bi bi-x-circle"></i> Hủy bỏ
    </button>

    <button type="button" class="btn btn-primary" onclick="readJSON_file_path(<?php echo calendarEventsText(json_encode($eventsFile, JSON_UNESCAPED_SLASHES)); ?>)">
        <i class="bi bi-eye"></i> Xem Dữ Liệu JSON
    </button>
</div>
	  
    </form>
    </div>
  </div></div>
  <div class="card alert alert-info"><div class="card-body pt-3"><h5 class="card-title">Kiểm Tra Chạy Thử (Test):</h5><div class="row g-2"><div class="col-md-5"><select id="simulate_event_index" class="form-select border-success"><?php foreach($eventsData['events'] as $index=>$event): ?><option value="<?php echo $index; ?>"><?php echo calendarEventsText(($event['name']??'').' — '.($event['id']??'')); ?></option><?php endforeach; ?></select></div><div class="col-md-4"><input type="datetime-local" id="simulate_event_at" class="form-control border-success" value="<?php echo date('Y-m-d\TH:i'); ?>"></div><div class="col-md-3"><button type="button" class="btn btn-warning w-100" id="simulate_event_button"><i class="bi bi-shield-check"></i> Kiểm Tra, Không Thực Thi</button></div></div><hr><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label" for="simulate_event_locale">File ngôn ngữ kiểm tra</label><select id="simulate_event_locale" class="form-select border-success"><?php foreach($eventLanguageOptions as $locale=>$languageName): ?><option value="<?php echo calendarEventsText($locale); ?>" <?php echo $locale===$activeEventLocale?'selected':''; ?>><?php echo calendarEventsText($languageName.' — '.$locale.'.json'); ?></option><?php endforeach; ?></select></div><div class="col-md-8"><label class="form-label" for="simulate_event_query">Thử câu lệnh, câu hỏi</label><div class="input-group"><input id="simulate_event_query" class="form-control border-success" placeholder="Ví dụ: Còn bao lâu nữa đến Tết Nguyên Đán?"><button type="button" class="btn btn-primary" id="simulate_event_query_button"><i class="bi bi-chat-dots"></i> Phân Tích Câu Hỏi</button></div></div></div><div class="form-text text-danger">Có thể thử: Hôm nay có sự kiện gì? · Tháng này có ngày quan trọng nào? · Đọc sự kiện gia đình sắp tới. Công cụ chỉ phân tích, không phát TTS và không thực thi hành động.</div><div id="simulate_event_result" class="alert alert-secondary mt-3 mb-0">Nội dung test câu lệnh sẽ được hiển thị ở đây</div></div></div>
  <div class="card alert alert-primary"><div class="card-body pt-3"><h5 class="card-title">Danh Sách Sự Kiện Đang Có (<?php echo count($eventsData['events']); ?>)</h5><div class="row g-2 mb-3"><div class="col-md-4"><input id="event_filter_text" class="form-control border-success" placeholder="Tìm theo tên hoặc ID"></div><div class="col-md-2"><select id="event_filter_calendar" class="form-select border-success"><option value="">Tất cả lịch</option><option value="solar">Lịch Dương</option><option value="lunar">Lịch Âm</option></select></div><div class="col-md-2"><select id="event_filter_active" class="form-select border-success"><option value="">Bật và tắt</option><option value="1">Đang bật</option><option value="0">Đang tắt</option></select></div><div class="col-md-2"><select id="event_filter_action" class="form-select border-success"><option value="">Mọi hành động</option><option value="1">Có hành động</option><option value="0">Không hành động</option></select></div><div class="col-md-2"><select id="event_sort" class="form-select border-success"><option value="date">Ngày gần nhất</option><option value="name">Tên A-Z</option></select></div></div><div class="table-responsive"><table class="table table-striped align-middle" id="calendar_events_table"><thead><tr><th class="text-danger">Tên Sự Kiện</th><th class="text-danger">Trạng thái</th><th class="text-danger">Ngày Tháng</th><th class="text-danger">Thời Gian</th><th class="text-danger">Thông báo</th><th class="text-danger">Hành động VBot</th><th class="text-danger">Thao Tác Khác</th></tr></thead><tbody>
  <?php foreach($eventsData['events'] as $index=>$event): $eventAction=!empty($event['execution']['active'])?($event['execution']['action']??'none'):'none'; $eventActionLabel=$eventAction==='none'?'Không Thực Hiện':($eventAction==='media_url'?'Phát media từ Link/URL':($eventAction==='media_local'?'Phát bài Local: '.basename($event['execution']['media_path']??''):$eventAction)); $eventMonths=$event['months']??[$event['month']??1]; $eventDays=$event['days']??[$event['day']??1]; $eventTimes=$event['times']??[$event['time']??($event['notification']['time']??'08:00')]; $eventEnabled=!empty($event['active']); $calendarLabel=($event['calendar']??'solar')==='lunar'?'Lịch Âm':'Lịch Dương'; ?><tr data-search="<?php echo calendarEventsText(strtolower(($event['name']??'').' '.($event['id']??''))); ?>" data-calendar="<?php echo calendarEventsText($event['calendar']??'solar'); ?>" data-active="<?php echo $eventEnabled?'1':'0'; ?>" data-action="<?php echo $eventAction!=='none'?'1':'0'; ?>" data-date="<?php echo sprintf('%04d-%02d-%02d', (int)(($event['year']??null)?:9999), (int)($eventMonths[0]??1), (int)($eventDays[0]??1)); ?>"><td><b><?php echo calendarEventsText($event['name']??''); ?></b><br><small>id: <?php echo calendarEventsText($event['id']??''); ?></small></td><td><span class="badge <?php echo $eventEnabled?'bg-success':'bg-secondary'; ?>"><?php echo $eventEnabled?'Đang bật':'Đang tắt'; ?></span></td><td><?php echo calendarEventsText($calendarLabel.' · '.(($event['year']??null)?:'Hằng Năm').' · Ngày '.implode(',',$eventDays).' · Tháng '.implode(',',$eventMonths)); ?></td><td><?php echo calendarEventsText(implode(', ',$eventTimes)); ?></td><td><?php echo !empty($event['notification']['active'])?'Có':'Không'; ?></td><td><?php echo calendarEventsText($eventActionLabel); ?></td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-primary edit-event-button" data-event-index="<?php echo $index; ?>" title="Sửa bằng JavaScript"><i class="bi bi-pencil"></i></button> <form method="post" class="d-inline" onsubmit="return confirm('Xóa event này?')"><input type="hidden" name="event_operation" value="delete"><input type="hidden" name="event_index" value="<?php echo $index; ?>"><input type="hidden" name="event_id" value="<?php echo calendarEventsText($event['id']??''); ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
  <div class="card alert alert-warning"><div class="card-body pt-3"><div class="d-flex justify-content-between align-items-center"><h5 class="card-title">Lịch Sử Chạy Sự Kiện (tối đa 200)</h5><form method="post" onsubmit="return confirm('Xóa toàn bộ lịch sử Events?')"><input type="hidden" name="event_operation" value="clear_history"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Xóa lịch sử</button></form></div><div class="table-responsive" style="max-height:320px"><table class="table table-sm"><thead><tr><th class="text-danger">Thời gian</th><th class="text-danger">Tên Sự Kiện</th><th class="text-danger">Loại</th><th class="text-danger">Trạng thái</th><th class="text-danger">Nội dung</th></tr></thead><tbody><?php foreach($history as $item): ?><tr><td><?php echo calendarEventsText($item['occurred_at']??''); ?></td><td><?php echo calendarEventsText($item['event_name']??$item['event_id']??''); ?></td><td><?php echo calendarEventsText(calendarEventsHistoryKindLabel($item['kind']??'')); ?></td><td><?php echo calendarEventsText(calendarEventsHistoryStatusLabel($item['status']??'')); ?></td><td><?php echo calendarEventsText($item['message']??''); ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
  <div class="card alert alert-secondary"><div class="card-body pt-3"><h5 class="card-title">Nhập, tải xuống và sao lưu</h5>
    <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mb-4">
      <input type="hidden" name="event_operation" value="import">
      <div class="col-md-5"><label class="form-label" for="events_import_file">Tải lên File khôi phục JSON (tối đa 512 KB)</label><input required accept="application/json,.json" type="file" class="form-control border-success" id="events_import_file" name="events_import_file"></div>
      <div class="col-md-3"><label class="form-label" for="events_import_mode">Cách nhập, khôi phục dữ liệu</label><select class="form-select border-success" id="events_import_mode" name="import_mode"><option value="merge">Gộp theo ID</option><option value="overwrite">Ghi đè toàn bộ</option></select></div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-success" type="submit" onclick="return confirm('Nhập file Events đã chọn? Dữ liệu hiện tại sẽ được sao lưu trước.')"><i class="bi bi-upload"></i> Tải Lên</button><button type="button" class="btn btn-primary" onclick="downloadFile(<?php echo calendarEventsText(json_encode($eventsFile, JSON_UNESCAPED_SLASHES)); ?>)"><i class="bi bi-download"></i> Tải Xuống</button></div>
      <div class="col-12 small text-muted">Tự giữ tối đa <b><?php echo $eventsBackupLimit; ?></b> bản tại: <code>html/Backup_Upgrade/Backup_Events/</code>.</div>
    </form>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>File sao lưu</th><th>Thời gian</th><th>Dung lượng</th><th></th></tr></thead><tbody>
      <?php if(!$backupFiles): ?><tr><td colspan="4" class="text-muted">Chưa có bản sao lưu.</td></tr><?php endif; ?>
      <?php foreach($backupFiles as $backupPath): $backupName=basename($backupPath); ?><tr><td><code><?php echo calendarEventsText($backupName); ?></code></td><td><?php echo date('d/m/Y H:i:s', filemtime($backupPath)?:time()); ?></td><td><?php echo number_format((filesize($backupPath)?:0)/1024, 1); ?> KB</td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-primary" title="Xem JSON" onclick="readJSON_file_path(<?php echo calendarEventsText(json_encode($backupPath, JSON_UNESCAPED_SLASHES)); ?>)"><i class="bi bi-eye"></i></button> <button type="button" class="btn btn-sm btn-success" title="Tải xuống" onclick="downloadFile(<?php echo calendarEventsText(json_encode($backupPath, JSON_UNESCAPED_SLASHES)); ?>)"><i class="bi bi-download"></i></button> <form method="post" class="d-inline" onsubmit="return confirm('Khôi phục bản sao lưu này? File hiện tại sẽ được sao lưu trước.')"><input type="hidden" name="event_operation" value="restore_backup"><input type="hidden" name="backup_file" value="<?php echo calendarEventsText($backupName); ?>"><button class="btn btn-sm btn-warning" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button></form> <form method="post" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn bản sao lưu này?')"><input type="hidden" name="event_operation" value="delete_backup"><input type="hidden" name="backup_file" value="<?php echo calendarEventsText($backupName); ?>"><button class="btn btn-sm btn-danger" title="Xóa"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div></div>
  <div class="modal fade" id="myModal_Config" tabindex="-1" aria-labelledby="modalLabel_Config" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title text-primary" id="name_file_showzz"></h5><button type="button" class="btn btn-danger" data-bs-dismiss="modal" onclick="if(window.jQuery)$('#myModal_Config').modal('hide')"><i class="bi bi-x-circle-fill"></i> Đóng</button></div><div class="modal-body"><p id="message_LoadConfigJson"></p><pre id="data" class="json rounded border p-3"><code id="code_config" class="language-json"></code></pre></div></div></div></div>
</main>
    <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>
function readJSON_file_path(filePath){read_loadFile(filePath);document.getElementById('name_file_showzz').textContent='Tên File: '+String(filePath).split('/').pop();if(window.bootstrap){bootstrap.Modal.getOrCreateInstance(document.getElementById('myModal_Config')).show()}else if(window.jQuery){$('#myModal_Config').modal('show')}}
const calendarEventIds=<?php echo json_encode(array_values(array_map(fn($event)=>(string)($event['id']??''),$eventsData['events'])), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
const calendarEventsData=<?php echo json_encode(array_values($eventsData['events']), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const simulateButton=document.getElementById('simulate_event_button');
if(simulateButton){simulateButton.parentElement.classList.add('d-grid','gap-2');simulateButton.insertAdjacentHTML('afterend','<button type="button" class="btn btn-danger w-100" id="execute_event_button"><i class="bi bi-play-fill"></i> Kiểm Tra Và Thực Thi Ngay</button>')}
const eventScheduleFields={months:{container:'event_months_list',name:'event_months[]',type:'number',min:'1',max:'12',placeholder:'Tháng'},days:{container:'event_days_list',name:'event_days[]',type:'number',min:'1',max:'31',placeholder:'Ngày'},times:{container:'event_times_list',name:'event_times[]',type:'time',placeholder:'Giờ'}};
function addEventScheduleValue(kind,value=''){const config=eventScheduleFields[kind],container=document.getElementById(config?.container);if(!config||!container)return;const group=document.createElement('div');group.className='input-group input-group-sm mb-2';const input=document.createElement('input');input.type=config.type;input.name=config.name;input.required=true;input.className='form-control border-success';input.placeholder=config.placeholder;if(config.min)input.min=config.min;if(config.max)input.max=config.max;input.value=value;const remove=document.createElement('button');remove.type='button';remove.className='btn btn-danger';remove.title='Xóa giá trị này';remove.innerHTML='<i class="bi bi-x-lg"></i>';remove.addEventListener('click',()=>{if(container.children.length>1)group.remove();else{input.value='';input.focus()}});group.append(input,remove);container.appendChild(group)}
function setEventScheduleValues(kind,values){const config=eventScheduleFields[kind],container=document.getElementById(config.container);container.replaceChildren();const list=Array.isArray(values)&&values.length?values:[''];list.forEach(value=>addEventScheduleValue(kind,String(value)))}
setEventScheduleValues('months',[1]);setEventScheduleValues('days',[1]);setEventScheduleValues('times',['08:00']);
function updateEventMediaUrlVisibility(){const select=document.getElementById('event_action'),group=document.getElementById('event_media_url_group'),input=document.getElementById('event_media_url'),visible=select?.value==='media_url';group?.classList.toggle('d-none',!visible);if(input)input.required=visible;window.calendarLocalPicker?.updateVisibility()}
function resetCalendarEventForm(){const form=document.getElementById('calendar-event-form');form.reset();form.querySelector('[name="event_index"]').value='';setEventScheduleValues('months',[1]);setEventScheduleValues('days',[1]);setEventScheduleValues('times',['08:00']);form.querySelector('[name="event_before_days"]').value='0';form.querySelector('[name="notification_repeat"]').value='1';form.querySelector('[name="event_media_url"]').value='';form.querySelector('[name="event_media_path"]').value='';window.calendarLocalPicker?.setSelected('');document.getElementById('event_active').checked=true;document.getElementById('notification_active').checked=true;document.getElementById('calendar_event_form_title').textContent='Thêm Mới Sự Kiện';document.getElementById('cancel_event_edit').classList.add('d-none');updateEventMediaUrlVisibility()}
function editCalendarEvent(index){const event=calendarEventsData[index],form=document.getElementById('calendar-event-form');if(!event)return;document.getElementById('calendar_event_form_container').classList.remove('d-none');const notification=event.notification||{},execution=event.execution||{};form.querySelector('[name="event_index"]').value=index;form.querySelector('[name="event_id"]').value=event.id||'';form.querySelector('[name="event_name"]').value=event.name||'';form.querySelector('[name="event_calendar"]').value=event.calendar||'solar';form.querySelector('[name="event_day"]').value=event.event_day??'';form.querySelector('[name="event_month"]').value=event.event_month??'';form.querySelector('[name="event_year"]').value=event.year??'';setEventScheduleValues('months',event.months||[event.month||1]);setEventScheduleValues('days',event.days||[event.day||1]);setEventScheduleValues('times',event.times||[event.time||notification.time||'08:00']);form.querySelector('[name="event_recurrence"]').value=event.recurrence||(event.year?'once':'yearly');form.querySelector('[name="event_leap_month"]').value=event.leap_month===true?'true':event.leap_month===false?'false':'';form.querySelector('[name="event_before_days"]').value=(notification.before_days||[0]).join(',');form.querySelector('[name="notification_repeat"]').value=notification.repeat||1;form.querySelector('[name="notification_message"]').value=notification.message||'';form.querySelector('[name="event_action"]').value=execution.active?(execution.action||'none'):'none';form.querySelector('[name="event_media_url"]').value=execution.media_url||'';form.querySelector('[name="event_media_path"]').value=execution.media_path||'';window.calendarLocalPicker?.setSelected(execution.media_path||'');form.querySelector('[name="event_tags"]').value=(event.tags||[]).join('\n');form.querySelector('[name="event_description"]').value=event.description||'';document.getElementById('event_active').checked=event.active!==false;document.getElementById('notification_active').checked=notification.active!==false&&notification.speaker!==false;document.getElementById('calendar_event_form_title').textContent='Sửa Sự Kiện: '+(event.name||event.id);document.getElementById('cancel_event_edit').classList.remove('d-none');updateEventMediaUrlVisibility();form.scrollIntoView({behavior:'smooth',block:'start'});setTimeout(()=>form.querySelector('[name="event_name"]').focus(),350)}
const editCalendarEventBase=editCalendarEvent;
editCalendarEvent=function(index){editCalendarEventBase(index);const event=calendarEventsData[index],form=document.getElementById('calendar-event-form');if(!event||!form)return;form.querySelector('[name="event_calendar"]').value=event.event_calendar||event.calendar||'solar';form.querySelector('[name="schedule_calendar"]').value=event.calendar||'solar'};
document.getElementById('show_event_form_button')?.addEventListener('click',()=>{resetCalendarEventForm();const container=document.getElementById('calendar_event_form_container');container.classList.remove('d-none');document.getElementById('cancel_event_edit').classList.remove('d-none');container.scrollIntoView({behavior:'smooth',block:'start'});setTimeout(()=>document.querySelector('[name="event_name"]').focus(),350)});
document.getElementById('event_action')?.addEventListener('change',updateEventMediaUrlVisibility);updateEventMediaUrlVisibility();
document.querySelectorAll('.edit-event-button').forEach(button=>button.addEventListener('click',()=>editCalendarEvent(Number(button.dataset.eventIndex))));document.getElementById('cancel_event_edit')?.addEventListener('click',()=>{resetCalendarEventForm();document.getElementById('calendar_event_form_container').classList.add('d-none');document.getElementById('show_event_form_button').scrollIntoView({behavior:'smooth',block:'center'})});
function validateCalendarEventForm(){const recurrence=document.querySelector('[name="event_recurrence"]').value;const year=document.querySelector('[name="event_year"]').value.trim();if(recurrence==='once'&&!year){show_message('Event một lần bắt buộc nhập năm.');return false}const action=document.getElementById('event_action').value;if(action!=='none'&&/(restart|reboot|shutdown|power)/i.test(action)){return confirm('Đây là hành động hệ thống có thể làm gián đoạn VBot. Bạn chắc chắn muốn lưu?')}return true}
document.getElementById('simulate_event_button')?.addEventListener('click',async function(){const result=document.getElementById('simulate_event_result');result.className='alert alert-info mt-3 mb-0';result.textContent='Đang mô phỏng...';const body=new URLSearchParams({event_operation:'simulate',event_index:document.getElementById('simulate_event_index').value,simulate_at:document.getElementById('simulate_event_at').value});try{const response=await fetch('Calendar_Events.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},body});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.error||'Mô phỏng thất bại');result.className='alert alert-success mt-3 mb-0';result.textContent=`Ngày chạy: ${data.target_date||'không còn lần chạy'} | Còn: ${data.days_remaining??'-'} ngày | Giờ: ${data.trigger_time} | Thông báo: ${data.notification.active?'Có':'Không'} | Hành động: ${data.execution.active?data.execution.action:'Không'} | Đã thực thi: Không`;}catch(error){result.className='alert alert-danger mt-3 mb-0';result.textContent=error.message}});
document.getElementById('execute_event_button')?.addEventListener('click',async function(){const select=document.getElementById('simulate_event_index'),eventId=calendarEventIds[Number(select.value)],result=document.getElementById('simulate_event_result');if(!eventId){result.className='alert alert-warning mt-3 mb-0';result.textContent='Không có Event hợp lệ để thực thi.';return}if(!confirm('Thực thi Event này ngay bây giờ? VBot sẽ phát loa và chạy hành động đã cấu hình. Hành động restart/reboot có thể làm mất kết nối WebUI.'))return;this.disabled=true;result.className='alert alert-warning mt-3 mb-0';result.textContent='Đang thực thi Event, vui lòng chờ...';try{const response=await fetch(<?php echo json_encode($URL_API_VBOT, JSON_UNESCAPED_SLASHES); ?>,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:3,data:'scheduler',value:'calendar_event_test',parameter:eventId})});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.message||'Thực thi Event thất bại');result.className='alert alert-success mt-3 mb-0';result.textContent='Đã thực thi: '+data.message;}catch(error){result.className='alert alert-danger mt-3 mb-0';result.textContent='Lỗi: '+error.message}finally{this.disabled=false}});
document.getElementById('simulate_event_query_button')?.addEventListener('click',async function(){const result=document.getElementById('simulate_event_result'),query=document.getElementById('simulate_event_query').value.trim(),locale=document.getElementById('simulate_event_locale').value;if(!query){result.className='alert alert-warning mt-3 mb-0';result.textContent='Hãy nhập câu hỏi cần thử.';return}result.className='alert alert-info mt-3 mb-0';result.textContent='Đang phân tích câu hỏi...';const body=new URLSearchParams({event_operation:'simulate_query',query,locale,simulate_at:document.getElementById('simulate_event_at').value});try{const response=await fetch('Calendar_Events.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},body});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.error||'Phân tích thất bại');const items=(data.items||[]).map(item=>`${item.name} (${item.date}, còn ${item.days} ngày)`).join('; ')||'Không tìm thấy Event';const keywords=(data.matched_keywords||[]).join(', ')||'Không có';const matchedEvents=(data.matched_events||[]).map(item=>`${item.name}: ${(item.terms||[]).join(', ')}`).join('; ')||'Không có';result.className=`alert ${data.events_active&&data.language_settings_complete?'alert-success':'alert-warning'} mt-3 mb-0`;result.replaceChildren();const lines=[`Trạng thái Events: ${data.events_active?'Đang bật':'Đang tắt — VBot thực tế sẽ không xử lý câu hỏi'}`,`File ngôn ngữ: ${data.locale}.json${data.language_settings_complete?'':' — thiếu values.calendar_events, đang dùng fallback'}`,`Kiểu nhận diện: ${data.mode}`,`Nhãn/ngày tương đối: ${data.label||data.target_date||'Không có'}`,`Keyword khớp: ${keywords}`,`Tên hoặc Tags khớp: ${matchedEvents}`,`Kết quả: ${items}`,'Phát TTS: Không · Thực thi hành động: Không'];lines.forEach(line=>{const div=document.createElement('div');div.textContent=line;result.appendChild(div)})}catch(error){result.className='alert alert-danger mt-3 mb-0';result.textContent=error.message}});
function filterCalendarEvents(){const text=document.getElementById('event_filter_text').value.toLowerCase().trim(),calendar=document.getElementById('event_filter_calendar').value,active=document.getElementById('event_filter_active').value,action=document.getElementById('event_filter_action').value,sort=document.getElementById('event_sort').value,body=document.querySelector('#calendar_events_table tbody'),rows=Array.from(body.querySelectorAll('tr'));rows.forEach(row=>{row.hidden=!!((text&&!row.dataset.search.includes(text))||(calendar&&row.dataset.calendar!==calendar)||(active&&row.dataset.active!==active)||(action&&row.dataset.action!==action))});rows.sort((a,b)=>sort==='name'?a.dataset.search.localeCompare(b.dataset.search,'vi'):a.dataset.date.localeCompare(b.dataset.date));rows.forEach(row=>body.appendChild(row))}['event_filter_text','event_filter_calendar','event_filter_active','event_filter_action','event_sort'].forEach(id=>document.getElementById(id)?.addEventListener(id==='event_filter_text'?'input':'change',filterCalendarEvents));filterCalendarEvents();
</script><script src="assets/js/calendar-event-local.js?v=1"></script></body></html>
