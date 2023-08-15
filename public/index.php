<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/MessageNormalizer.php';
require_once __DIR__ . '/../src/SpamDetector.php';

use src\SpamDetector;

//Редис
$redisConfig = [
    'scheme' => 'tcp',
    'host'   => 'redis',
    'port'   => 6379,
];

//создаем инстанс SpamDetector-а
$spamDetector = new \src\SpamDetector(__DIR__ . '/../docs/stopwords.txt', __DIR__ . '/../docs/blocklist.txt', $redisConfig);

//Проверяем что был сделан POST запрос с нужными данными (сообщение, айди клиента)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['clientID'])) {
    $message = $_POST['message'];
    $clientID = $_POST['clientID'];
    $checkRate = isset($_POST['check_rate']) && $_POST['check_rate'] == '1';

    //проверяем сообщение на спам
    $result = $spamDetector->checkSpam($message, $clientID, $checkRate);

    header('Content-type: application/json; charset=utf-8');
    echo json_encode($result);
} else {
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
}
