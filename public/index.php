<?php

namespace src;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/MessageNormalizer.php';
require_once __DIR__ . '/../src/SpamDetector.php';
require_once __DIR__ . '/../src/RequestHandler.php';
require_once __DIR__ . '/../src/SessionManager.php';

// Конфигурация Redis
$redisConfig = [
    'scheme' => 'tcp',
    'host'   => 'redis',
    'port'   => 6379,
];

$spamDetector = new SpamDetector(__DIR__ . '/../docs/stopwords.txt', __DIR__ . '/../docs/blocklist.txt', $redisConfig);
$messageNormalizer = new MessageNormalizer(__DIR__ . '/../docs/stopwords.txt');

// Управление сессиями
$sessionManager = new SessionManager();
$sessionManager->start();
$sessionManager->ensureClientSessionId();

// Обработка запроса и вывод ответа
$requestHandler = new RequestHandler($spamDetector, $messageNormalizer);
echo $requestHandler->handle();
