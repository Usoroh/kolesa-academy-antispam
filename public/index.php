<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/MessageNormalizer.php';
require_once __DIR__ . '/../src/SpamDetector.php';

//Редис
$redisConfig = [
    'scheme' => 'tcp',
    'host'   => 'redis',
    'port'   => 6379,
];

// создаем инстанс SpamDetector-а
$spamDetector = new \src\SpamDetector(__DIR__ . '/../docs/stopwords.txt', __DIR__ . '/../docs/blocklist.txt', $redisConfig);

// создаем айди сессии чтобы следить за кол-вом отправленных сообщений
if (!isset($_SESSION['client_session_id'])) {
    $_SESSION['client_session_id'] = session_create_id();
}

// Проверяем что был сделан POST запрос с нужными данными
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
    $message = $_POST['text'];
    $clientID = $_SESSION['client_session_id'];
    $checkRate = isset($_POST['check_rate']) && $_POST['check_rate'] == '1';

    // проверяем сообщение на спам
    $result = $spamDetector->checkSpam($message, $clientID, $checkRate);

    // составляем ответ
    $response = [
        'status' => 'ok',
        'spam' => $result['is_spam'],
        'reason' => $result['reason'] ?? "",
        'normalized_text' => (new \src\MessageNormalizer(__DIR__ . '/../docs/stopwords.txt'))->normalize($message)
    ];

    header('Content-type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode($response);
} else {
    header('Content-type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
}
