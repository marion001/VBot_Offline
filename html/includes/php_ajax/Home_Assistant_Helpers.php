<?php

if (!function_exists('vbotHassYamlScalar')) {
function vbotHassYamlScalar($value)
{
    $value = trim((string)$value);
    if ($value === '{}') return [];
    if ($value === '[]') return [];
    if ($value === 'null' || $value === '~') return null;
    if (strcasecmp($value, 'true') === 0) return true;
    if (strcasecmp($value, 'false') === 0) return false;
    if (preg_match('/^-?\d+$/', $value)) return (int)$value;
    if (is_numeric($value)) return (float)$value;
    if (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : substr($value, 1, -1);
    }
    if (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
        return str_replace("''", "'", substr($value, 1, -1));
    }
    if (($value[0] ?? '') === '[' || ($value[0] ?? '') === '{') {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }
    return $value;
}

function vbotHassParseActionYaml($yaml, &$error = null)
{
    $error = null;
    if (!is_string($yaml) || trim($yaml) === '') {
        $error = 'YAML không được để trống';
        return null;
    }
    $result = [];
    $path = [];
    foreach (preg_split('/\R/u', trim($yaml)) as $lineNumber => $line) {
        if (trim($line) === '' || preg_match('/^\s*#/', $line)) continue;
        if (strpos($line, "\t") !== false) {
            $error = 'YAML không được dùng tab ở dòng '.($lineNumber + 1);
            return null;
        }
        preg_match('/^(\s*)/', $line, $indentMatch);
        $spaces = strlen($indentMatch[1]);
        if ($spaces % 2 !== 0) {
            $error = 'Thụt dòng YAML phải là bội số của 2 ở dòng '.($lineNumber + 1);
            return null;
        }
        $level = intdiv($spaces, 2);
        $trimmed = trim($line);
        while (count($path) > $level) array_pop($path);
        $parent =& $result;
        foreach ($path as $segment) {
            if (!isset($parent[$segment]) || !is_array($parent[$segment])) $parent[$segment] = [];
            $parent =& $parent[$segment];
        }
        if (strpos($trimmed, '-') === 0 && preg_match('/^-\s+(.+)$/', $trimmed, $listMatch)) {
            $parent[] = vbotHassYamlScalar($listMatch[1]);
            unset($parent);
            continue;
        }
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_-]*)\s*:\s*(.*)$/', $trimmed, $matches)) {
            $error = 'Cú pháp YAML không hợp lệ ở dòng '.($lineNumber + 1);
            return null;
        }
        $key = $matches[1];
        $rawValue = $matches[2];
        if ($rawValue === '') {
            $parent[$key] = [];
            $path[] = $key;
        } else {
            $parent[$key] = vbotHassYamlScalar($rawValue);
        }
        unset($parent);
    }
    return $result;
}

function vbotHassValidateAction(array $actionData, &$error = null)
{
    $error = null;
    if (!isset($actionData['action']) && isset($actionData['service'])) {
        $actionData['action'] = $actionData['service'];
        unset($actionData['service']);
    }
    $action = $actionData['action'] ?? '';
    if (!is_string($action) || !preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/i', $action)) {
        $error = 'action phải đúng định dạng domain.service';
        return null;
    }
    $data = $actionData['data'] ?? [];
    if ($data === '{}') $data = [];
    if (!is_array($data)) {
        $error = 'data phải là object YAML';
        return null;
    }
    $target = $actionData['target'] ?? [];
    if (!is_array($target)) {
        $error = 'target phải là object YAML';
        return null;
    }
    foreach ($target as $key => $value) {
        if (!in_array($key, ['entity_id', 'device_id', 'area_id'], true)) {
            $error = 'target không hỗ trợ khóa: '.$key;
            return null;
        }
        $values = is_array($value) ? $value : [$value];
        if (!$values) {
            $error = 'target.'.$key.' không được rỗng';
            return null;
        }
        foreach ($values as $item) {
            if (!is_string($item) || trim($item) === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $item)) {
                $error = 'Giá trị target.'.$key.' không hợp lệ';
                return null;
            }
        }
    }
    return [
        'action' => strtolower($action),
        'data' => empty($data) ? (object)[] : $data,
        'target' => empty($target) ? (object)[] : $target,
    ];
}

function vbotHassValidateIntentDocument($document, &$error = null)
{
    $error = null;
    if (!is_array($document) || !isset($document['intents']) || !is_array($document['intents'])) {
        $error = 'Thiếu danh sách intents hợp lệ';
        return null;
    }
    $normalized = [];
    $questionsSeen = [];
    foreach ($document['intents'] as $index => $intent) {
        if (!is_array($intent)) {
            $error = 'Intent số '.($index + 1).' không phải object';
            return null;
        }
        $name = trim((string)($intent['name'] ?? ''));
        $questions = $intent['questions'] ?? null;
        $actionError = null;
        $action = is_array($intent['data_yaml'] ?? null)
            ? vbotHassValidateAction($intent['data_yaml'], $actionError)
            : null;
        if ($name === '' || !is_array($questions) || !$questions || $action === null) {
            $error = 'Intent số '.($index + 1).' không hợp lệ: '.($actionError ?: 'thiếu name/questions');
            return null;
        }
        $cleanQuestions = [];
        foreach ($questions as $question) {
            $question = trim((string)$question);
            if ($question === '') continue;
            $key = preg_replace('/\s+/u', ' ', $question);
            $key = function_exists('mb_strtolower') ? mb_strtolower($key, 'UTF-8') : strtolower($key);
            if (isset($questionsSeen[$key])) {
                $error = 'Câu lệnh bị trùng giữa '.$questionsSeen[$key].' và '.$name.': '.$question;
                return null;
            }
            $questionsSeen[$key] = $name;
            $cleanQuestions[] = $question;
        }
        if (!$cleanQuestions) {
            $error = 'Intent '.$name.' không có câu lệnh';
            return null;
        }
        $normalized[] = [
            'name' => $name,
            'reply' => trim((string)($intent['reply'] ?? '')),
            'data_yaml' => $action,
            'questions' => $cleanQuestions,
            'active' => !empty($intent['active']),
        ];
    }
    return ['intents' => $normalized];
}
}
