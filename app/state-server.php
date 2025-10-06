<?php

/**
 * Скрипт для запуска сервера метрик
 * 
 * Использование:
 *   php state-server.php [host] [port]
 * 
 * Примеры:
 *   php state-server.php
 *   php state-server.php 0.0.0.0 8080
 *   php state-server.php 127.0.0.1 9000
 */

 require __DIR__ . '/vendor/autoload.php';

use Anymodule\Agentmodule\State;

$host = $argv[1] ?? '0.0.0.0';
$port = (int)($argv[2] ?? 8484);

$server = new State($host, $port);
$server->run();

