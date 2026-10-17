<?php
// Fixed command arrays only; no shell, SSH channel or user-supplied executable.
function vbotRunLocalProcess(array $command, string $input='', float $timeout=5.0): array {
    // File descriptors avoid pipe deadlocks and work on Windows PHP as well.
    $streams=[tmpfile(),tmpfile(),tmpfile()];
    foreach ($streams as $stream) if (!is_resource($stream)) {
        foreach ($streams as $opened) if (is_resource($opened)) fclose($opened);
        throw new RuntimeException('Không tạo được bộ đệm cho công cụ hệ thống');
    }
    $process=null;
    $output=''; $error=''; $exit=-1;
    try {
        if ($input !== '') {
            $written=0;
            while ($written < strlen($input)) {
                $n=fwrite($streams[0], substr($input, $written));
                if (!$n) throw new RuntimeException('Không gửi được cấu hình cho công cụ hệ thống');
                $written += $n;
            }
        }
        rewind($streams[0]);
        $process = @proc_open($command, $streams, $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($process)) throw new RuntimeException('Không khởi chạy được công cụ hệ thống');
        $deadline=microtime(true)+$timeout;
        do {
            if (fstat($streams[1])['size']+fstat($streams[2])['size']>2097152) throw new RuntimeException('Phản hồi vượt quá giới hạn');
            $status=proc_get_status($process);
            if (!$status['running']) { $exit=$status['exitcode']; break; }
            if (microtime(true)>$deadline) throw new RuntimeException('Công cụ hệ thống hết thời gian chờ');
            usleep(20000);
        } while (true);
        rewind($streams[1]); rewind($streams[2]);
        $output=stream_get_contents($streams[1]); $error=stream_get_contents($streams[2]);
    } finally {
        if (is_resource($process)) {
            $status=proc_get_status($process);
            if ($status['running']) proc_terminate($process, 9);
            $closed=proc_close($process);
            if ($exit<0) $exit=$closed;
        }
        foreach ($streams as $stream) if (is_resource($stream)) fclose($stream);
    }
    return ['exit'=>$exit,'stdout'=>$output,'stderr'=>$error];
}
