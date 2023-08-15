<?php

namespace src;

class RequestHandler {
    private $spamDetector;   // Экземпляр класса SpamDetector для проверки на спам
    private $messageNormalizer;  // Экземпляр класса MessageNormalizer для нормализации сообщений

    public function __construct(SpamDetector $spamDetector, MessageNormalizer $messageNormalizer) {
        $this->spamDetector = $spamDetector;
        $this->messageNormalizer = $messageNormalizer;
    }

    /**
     * Обработка запросов к сервису.
     *
     * @return string Ответ в формате JSON.
     */
    public function handle(): string {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                return $this->handleGetRequest();

            case "POST":
                return $this->handlePostRequest();

            default:
                return $this->handleInvalidMethod();
        }
    }

    /**
     * Обработка GET-запроса.
     *
     * @return string Ответ в формате JSON.
     */
    private function handleGetRequest(): string {
        http_response_code(200);
        return json_encode(['status' => 'ok', 'message' => 'Антиспам работает']);
    }

    /**
     * Обработка POST-запроса.
     *
     * @return string Ответ в формате JSON.
     */
    private function handlePostRequest(): string {
        if (!isset($_POST['text'])) {
            http_response_code(400);
            return json_encode(['status' => 'error', 'message' => 'field text required']);
        }

        $message = $_POST['text'];
        $clientID = $_SESSION['clientSessionId'];
        $checkRate = isset($_POST['check_rate']) && $_POST['check_rate'] == '1';

        $result = $this->spamDetector->checkSpam($message, $clientID, $checkRate);

        $response = [
            'status' => 'ok',
            'spam' => $result['is_spam'],
            'reason' => $result['reason'] ?? "",
            'normalized_text' => $this->messageNormalizer->normalize($message)
        ];

        http_response_code(200);
        return json_encode($response);
    }

    /**
     * Обработка неизвестных методов.
     *
     * @return string Ответ в формате JSON.
     */
    private function handleInvalidMethod(): string {
        http_response_code(405);
        return json_encode(['status' => 'error', 'message' => 'Метод не разрешен']);
    }
}
