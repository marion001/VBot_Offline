<?php
function vbotActionRegistryStatic(): array
{
  static $actions = null;
  if ($actions !== null) return $actions;
  $path = dirname(__DIR__, 2).'/resource/action_registry.json';
  $data = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
  $actions = [];
  foreach (($data['actions'] ?? []) as $item) {
    $id = trim((string)($item['id'] ?? ''));
    if ($id !== '') $actions[$id] = (string)($item['label'] ?? $id);
  }
  if (!$actions) $actions = ['none'=>'Không thực hiện'];
  return $actions;
}

function vbotActionRegistryStates(): array
{
  $path = dirname(__DIR__, 2).'/resource/action_registry.json';
  $data = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
  $states = [];
  foreach (($data['button_states'] ?? []) as $item) {
    $id = trim((string)($item['id'] ?? ''));
    if ($id !== '') $states[$id] = (string)($item['label'] ?? $id);
  }
  return $states;
}

function vbotActionRegistryOptions(array $Config, bool $includeDeviceActions=false): array
{
  static $requestCache = [];
  $cacheKey = hash('sha256', serialize([
    $includeDeviceActions,
    $Config['media_player']['radio_data'] ?? [],
    $Config['wake_on_lan']['active'] ?? null,
    $Config['internal_ir']['json_file'] ?? 'resource/internal_ir/commands.json',
  ]));
  if (array_key_exists($cacheKey, $requestCache)) return $requestCache[$cacheKey];

  $options = vbotActionRegistryStatic();
  $manifestPath = __DIR__.'/cache/PlayLists.json';
  $manifest = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : [];
  if (!isset($options['playlist:default'])) $options['playlist:default'] = 'Phát Playlist: Mặc định';
  foreach (($manifest['playlists'] ?? []) as $playlist) {
    $id = trim((string)($playlist['id'] ?? ''));
    if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) $options['playlist:'.$id] = 'Phát Playlist: '.($playlist['name'] ?? $id);
  }
  foreach (($Config['media_player']['radio_data'] ?? []) as $radio) {
    $name = trim((string)($radio['name'] ?? '')); $link = trim((string)($radio['link'] ?? ''));
    if ($name !== '' && $link !== '') $options['radio:'.substr(sha1($name."\n".$link), 0, 12)] = 'Phát Radio: '.$name;
  }
  if (!$includeDeviceActions) return $options;
  $root = dirname(__DIR__, 2);
  $routinePath = $root.'/resource/voice_routines/voice_routines.json';
  if (!is_file($routinePath)) $routinePath = $root.'/resource/voice_routines.json';
  $routines = is_file($routinePath) && (@filesize($routinePath) ?: 0) <= 1048576
    ? json_decode((string)@file_get_contents($routinePath), true) : [];
  foreach (is_array($routines['routines'] ?? null) ? $routines['routines'] : [] as $routine) {
    if (!is_array($routine)) continue;
    $id = $routine['id'] ?? '';
    if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $id)) {
      $options['routine:'.$id] = 'Chạy kịch bản: '.($routine['name'] ?? $id)
        .(($routine['enabled'] ?? false) ? '' : ' (đang tắt)');
    }
  }
  $wolPath = $root.'/resource/wake_on_lan/devices.json';
  $wolData = is_file($wolPath) && (@filesize($wolPath) ?: 0) <= 1048576
    ? json_decode((string)@file_get_contents($wolPath), true) : [];
  foreach (is_array($wolData['devices'] ?? null) ? $wolData['devices'] : [] as $device) {
    if (!is_array($device)) continue;
    $id=$device['id']??'';
    if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)) {
      $enabled=($Config['wake_on_lan']['active']??$wolData['active']??false) && ($device['enabled']??false);
      $options['wol:'.$id]='Bật máy tính (Wake-on-LAN): '.($device['name']??$id).($enabled?'':' (đang tắt)');
    }
  }
  $configuredIrPath = trim((string)($Config['internal_ir']['json_file'] ?? 'resource/internal_ir/commands.json'));
  $irPath = preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $configuredIrPath)
    ? $configuredIrPath : $root.'/'.ltrim($configuredIrPath, '/\\');
  $ir = is_file($irPath) && (@filesize($irPath) ?: 0) <= 20971520
    ? json_decode((string)@file_get_contents($irPath), true) : [];
  foreach (is_array($ir['commands'] ?? null) ? $ir['commands'] : [] as $command) {
    if (!is_array($command) || ($command['active'] ?? true) !== true) continue;
    $name = trim((string)($command['name'] ?? ''));
    if ($name !== '' && strlen($name) <= 100) {
      $token = sha1($name);
      $options['ir:'.$token] = 'Phát lệnh IR: '.$name;
    }
  }
  $routineHelper = __DIR__.'/VoiceRoutines.php';
  if (!function_exists('vbotRoutineTuyaEntries') && is_file($routineHelper)) require_once $routineHelper;
  if (function_exists('vbotRoutineTuyaEntries')) foreach (vbotRoutineTuyaEntries($root) as $entry) {
    $token = rtrim(strtr(base64_encode(json_encode([$entry['device_id'], $entry['entry_key']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    $options['tuya:'.$token.':on'] = 'Tuya: '.$entry['label'].' - Bật';
    $options['tuya:'.$token.':off'] = 'Tuya: '.$entry['label'].' - Tắt';
  }
  $hassPath = $root.'/resource/hass/Home_Assistant.json';
  $hass = is_file($hassPath) && (@filesize($hassPath) ?: 0) <= 20971520
    ? json_decode((string)@file_get_contents($hassPath), true) : [];
  $hassActions = ['light'=>['turn_on','turn_off'],'fan'=>['turn_on','turn_off'],'switch'=>['turn_on','turn_off'],
    'input_boolean'=>['turn_on','turn_off'],'climate'=>['turn_on','turn_off'],'cover'=>['turn_open','turn_close','turn_stop'],
    'script'=>['turn_on'],'scene'=>['turn_on']];
  foreach (is_array($hass['get_hass_all'] ?? null) ? $hass['get_hass_all'] : [] as $entity) {
    if (!is_array($entity)) continue;
    $entityId = (string)($entity['entity_id'] ?? '');
    if (!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/D', $entityId)) continue;
    $domain = strstr($entityId, '.', true);
    foreach ($hassActions[$domain] ?? [] as $service) {
      $token = rtrim(strtr(base64_encode(json_encode([$entityId, $service])), '+/', '-_'), '=');
      $serviceLabels = ['turn_on'=>'Bật - turn_on', 'turn_off'=>'Tắt - turn_off',
        'turn_open'=>'Mở - turn_open', 'turn_close'=>'Đóng - turn_close', 'turn_stop'=>'Dừng - turn_stop'];
      $options['hass:'.$token] = 'Home Assistant: '.($entity['attributes']['friendly_name'] ?? $entityId).' — '.($serviceLabels[$service] ?? $service);
    }
    if (in_array($domain, ['light','fan','switch','input_boolean','climate'], true)) {
      $toggleToken = rtrim(strtr(base64_encode(json_encode([$entityId, 'toggle'])), '+/', '-_'), '=');
      $options['toggle:hass:'.$toggleToken] = 'Home Assistant: '.($entity['attributes']['friendly_name'] ?? $entityId).' — Đảo bật/tắt';
    }
  }
  foreach ($options as $id => $label) {
    if (preg_match('/^tuya:/', $id)) {
      $toggleLabel = preg_replace('/^Tuya: (.*) - (?:Bật|Tắt)$/u', 'Tuya: $1', $label);
      $options['toggle:'.$id] = 'Đảo bật/tắt: '.$toggleLabel;
    }
  }
  $requestCache[$cacheKey] = $options;
  return $options;
}

function vbotActionRegistryNormalize(array $Config, $action): string
{
  $action = trim((string)$action);
  if (strpos($action, 'vbot_action:') === 0) $action = substr($action, 12);
  if (array_key_exists($action, vbotActionRegistryOptions($Config, true))) return $action;
  if (array_key_exists($action, vbotActionRegistryOptions($Config))) return $action;
  if (preg_match('/^(?:toggle:)?(?:(?:routine|wol):[A-Za-z0-9_-]{1,64}|playlist:[A-Za-z0-9_-]{1,64}|radio:[0-9a-f]{12}|ir:[0-9a-f]{40}|tuya:[A-Za-z0-9_-]{1,260}:(?:on|off)|hass:[A-Za-z0-9_-]{1,220})$/D', $action)) return $action;
  return 'none';
}

function vbotActionRegistryRender(array $Config, $selected, string $prefix='', bool $includeDeviceActions=true): string
{
  $selected = trim((string)$selected);
  if (strpos($selected, 'vbot_action:') === 0) $selected = substr($selected, 12);
  $options = vbotActionRegistryOptions($Config, $includeDeviceActions);
  if ($selected !== '' && !array_key_exists($selected, $options)) {
    $options[$selected] = 'Thao tác đã lưu (chưa có trong danh sách tải nhanh)';
  }
  $html = '';
  foreach ($options as $value => $label) {
    $stored = $prefix.$value;
    $html .= '<option value="'.htmlspecialchars($stored, ENT_QUOTES, 'UTF-8').'"'.($value === $selected ? ' selected' : '').'>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
  }
  return $html;
}
