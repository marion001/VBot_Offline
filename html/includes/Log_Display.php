<?php
function vbotFormatLogTimestamps($content)
{
    return preg_replace_callback(
        '/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2})(?: ([^\]\r\n]+))?\]/m',
        static function ($match) {
            try {
                $zone = new DateTimeZone($match[2] ?? 'Asia/Ho_Chi_Minh');
                $date = DateTimeImmutable::createFromFormat('!d-M-Y H:i:s', $match[1], $zone);
                $errors = DateTimeImmutable::getLastErrors();
                if ($date === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
                    return $match[0];
                }
                return '['.$date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('H:i:s d-m-Y').']';
            } catch (Throwable $error) {
                return $match[0];
            }
        },
        $content
    );
}
