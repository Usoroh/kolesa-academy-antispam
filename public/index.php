<?php

session_start();

// Подключаем необходимые библиотеки и классы
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/MessageNormalizer.php';
require_once __DIR__ . '/../src/SpamDetector.php';

// Настройка Редис
$redisConfig = [
    'scheme' => 'tcp',
    'host'   => 'redis',
    'port'   => 6379,
];

// Создаем экземпляр класса SpamDetector
$spamDetector = new \src\SpamDetector(__DIR__ . '/../docs/stopwords.txt', __DIR__ . '/../docs/blocklist.txt', $redisConfig);

// Создаем идентификатор сессии для отслеживания сообщений пользователя
if (!isset($_SESSION['client_session_id'])) {
    $_SESSION['client_session_id'] = session_create_id();
}

// Заголовок ответа
header('Content-type: application/json; charset=utf-8');

// Обрабатываем запрос в зависимости от его метода
switch ($_SERVER['REQUEST_METHOD']) {
    case "GET":
        // Если это GET-запрос, просто возвращаем статус сервиса
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Антиспам работает']);
        break;

    case "POST":
        // Если POST-запрос не содержит текст, возвращаем ошибку
        if (!isset($_POST['text'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'field text required']);
            break;
        }

        // Получаем сообщение и информацию о клиенте
        $message = $_POST['text'];
        $clientID = $_SESSION['client_session_id'];
        $checkRate = isset($_POST['check_rate']) && $_POST['check_rate'] == '1';

        // Проверяем сообщение на спам
        $result = $spamDetector->checkSpam($message, $clientID, $checkRate);

        // Формируем и отправляем ответ
        $response = [
            'status' => 'ok',
            'spam' => $result['is_spam'],
            'reason' => $result['reason'] ?? "",
            'normalized_text' => (new \src\MessageNormalizer(__DIR__ . '/../docs/stopwords.txt'))->normalize($message)
        ];

        http_response_code(200);
        echo json_encode($response);
        break;

    default:
        // Если используется другой HTTP-метод, возвращаем ошибку
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Метод не разрешен']);
        break;
}
