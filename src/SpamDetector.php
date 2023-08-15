<?php

namespace src;

use Predis\Client as RedisClient;

class SpamDetector
{
    private $normalizer;  // Нормализатор сообщений
    private $redis;       // Клиент Redis
    private $blockList;   // Список запрещенных слов

    /**
     * Конструктор класса SpamDetector.
     *
     * @param string $stopWordsPath Путь к файлу со стоп-словами.
     * @param string $blockListPath Путь к файлу со списком заблокированных слов.
     * @param array $redisConfig Конфигурация для Redis.
     */
    public function __construct(string $stopWordsPath, string $blockListPath, array $redisConfig = [])
    {
        $this->normalizer = new MessageNormalizer($stopWordsPath);  // Инициализация нормализатора сообщений
        $this->redis = new RedisClient($redisConfig);  // Инициализация клиента Redis
        $this->blockList = file($blockListPath, FILE_IGNORE_NEW_LINES);  // Загрузка списка заблокированных слов
    }

    /**
     * Проверка на дубликат.
     *
     * @param array $normalizedTokens Токены нормализованного сообщения.
     * @param string $clientID ID клиента.
     * @return bool Возвращает true, если сообщение является дубликатом.
     */
    private function isDuplicate(array $normalizedTokens, string $clientID): bool
    {
        $previousTokensKey = "previousTokens:$clientID";  // Ключ для хранения предыдущих токенов в Redis
        // Получаем предыдущие токены из редиса
        $previousTokens = $this->redis->sMembers($previousTokensKey);
        // Пропускаем проверку если предыдущих токенов нет или их количество меньше 3
        if (!$previousTokens || count($normalizedTokens) < 3) {
            $this->redis->del($previousTokensKey);  // Удаляем старые токены
            foreach ($normalizedTokens as $token) {
                $this->redis->sAdd($previousTokensKey, $token);  // Добавляем новые токены в Redis
            }
            return false;
        }

        // Вычисляем процент совпадения токенов с предыдущим сообщением
        $matchingTokens = count(array_intersect($normalizedTokens, $previousTokens));
        $percentageMatching = ($matchingTokens / count($normalizedTokens)) * 100;

        $this->redis->del($previousTokensKey);  // Удаляем старые токены
        foreach ($normalizedTokens as $token) {
            $this->redis->sAdd($previousTokensKey, $token);  // Добавляем новые токены в Redis
        }

        return $percentageMatching >= 60;  // Считаем сообщение дубликатом, если совпадение больше или равно 60%
    }

    /**
     * Проверка на спам.
     *
     * @param string $message Сообщение для проверки.
     * @param string $clientID ID клиента.
     * @param bool $checkRate Флаг для проверки частоты отправки сообщений.
     * @return array Возвращает массив с результатами проверки.
     */
    public function checkSpam(string $message, string $clientID, bool $checkRate = false): array
    {
        $normalizedTokens = explode(' ', $this->normalizer->normalize($message));  // Нормализация сообщения
        if ($this->isDuplicate($normalizedTokens, $clientID)) {  // Проверка на дубликат
            return ['is_spam' => true, 'reason' => 'duplicate'];
        }

        // Проверка частоты отправки сообщений
        $lastMessageTimeKey = "lastMessageTime:$clientID";  // Ключ для хранения времени последнего сообщения в Redis
        $lastMessageTime = $this->redis->get($lastMessageTimeKey);
        if ($checkRate && $lastMessageTime && (time() - (int)$lastMessageTime < 3)) {
            return ['is_spam' => true, 'reason' => 'check_rate'];
        }

        // Обновляем время последнего сообщения, если оно не спам или прошло больше 3 секунд с последнего сообщения
        if (!$checkRate || ($lastMessageTime && (time() - (int)$lastMessageTime >= 3))) {
            $this->redis->set($lastMessageTimeKey, time());
        }

        // Проверка на наличие слов из списка запрещенных слов
        $normalizedMessage = $this->normalizer->normalize($message);
        foreach (explode(' ', $normalizedMessage) as $word) {
            if (in_array($word, $this->blockList)) {
                return ['is_spam' => true, 'reason' => 'block_list'];
            }
        }

        // Проверка на наличие электронной почты в сообщении
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}/', $message)) {
            return ['is_spam' => true, 'reason' => 'block_list'];
        }

        // Проверка на смешанные слова (латиница и кириллица в одном слове)
        if (preg_match('/[a-zA-Z].*[А-я]|[А-я].*[a-zA-Z]/', $message)) {
            return ['is_spam' => true, 'reason' => 'mixed_words'];
        }

        // Возвращаем результат: сообщение не является спамом
        return ['is_spam' => false];
    }
}
