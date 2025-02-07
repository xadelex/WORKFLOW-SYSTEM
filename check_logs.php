<?php
$logsDir = __DIR__ . '/logs';

if (!file_exists($logsDir)) {
    if (!mkdir($logsDir, 0755, true)) {
        die('Logs dizini oluşturulamadı');
    }
}

if (!is_writable($logsDir)) {
    die('Logs dizini yazılabilir değil');
}

echo "Logs dizini hazır ve yazılabilir."; 