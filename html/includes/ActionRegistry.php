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

function vbotActionRegistryOptions(array $Config): array
{
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
  return $options;
}

function vbotActionRegistryNormalize(array $Config, $action): string
{
  $action = trim((string)$action);
  if (strpos($action, 'vbot_action:') === 0) $action = substr($action, 12);
  return array_key_exists($action, vbotActionRegistryOptions($Config)) ? $action : 'none';
}

function vbotActionRegistryRender(array $Config, $selected, string $prefix=''): string
{
  $selected = vbotActionRegistryNormalize($Config, $selected);
  $html = '';
  foreach (vbotActionRegistryOptions($Config) as $value => $label) {
    $stored = $prefix.$value;
    $html .= '<option value="'.htmlspecialchars($stored, ENT_QUOTES, 'UTF-8').'"'.($value === $selected ? ' selected' : '').'>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
  }
  return $html;
}
