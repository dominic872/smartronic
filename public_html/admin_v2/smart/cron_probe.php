<?php
date_default_timezone_set('Asia/Kolkata');

$line = '[' . date('Y-m-d H:i:s') . '] cron_probe.php executed; sapi=' . PHP_SAPI . '; cwd=' . getcwd() . PHP_EOL;
file_put_contents(__DIR__ . '/cron_probe.txt', $line, FILE_APPEND | LOCK_EX);
echo $line;
