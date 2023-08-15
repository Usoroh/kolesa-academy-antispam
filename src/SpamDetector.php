<?php

namespace src;

use Predis\Client as RedisClient;

class SpamDetector
{
    private $normalizer;
    private $redis;
    private $blockList;

    public function __construct($stopWordsPath, $blockListPath, $redisConfig = [])
    {
        $this->normalizer = new MessageNormalizer($stopWordsPath);
        $this->redis = new RedisClient($redisConfig);
        $this->blockList = file($blockListPath, FILE_IGNORE_NEW_LINES);
    }

    // метод для проверки дубликатов
    private function isDuplicate($normalizedTokens, $clientID) {
        $previousTokensKey = "previousTokens:$clientID";
        // Получаем предыдущие токены из редиса
        $previousTokens = $this->redis->sMembers($previousTokensKey);
        // Если предыдущих токенов нет или в текущем сообщении меньше 3 токенов, пропускаем проверку
        if (!$previousTokens || count($normalizedTokens) < 3) {
            // Обновляем список предыдущих токенов в редисе
            $this->redis->del($previousTokensKey);
            foreach ($normalizedTokens as $token) {
                $this->redis->sAdd($previousTokensKey, $token);
            }
            return false;
        }

        // Вычисляем процент соответствия токенов между текущим и предыдущим сообщением
        $matchingTokens = count(array_intersect($normalizedTokens, $previousTokens));
        $percentageMatching = ($matchingTokens / count($normalizedTokens)) * 100;

        // Обновляем список предыдущих токенов в редисе для следующей проверки
        $this->redis->del($previousTokensKey);
        foreach ($normalizedTokens as $token) {
            $this->redis->sAdd($previousTokensKey, $token);
        }

        // Возвращаем true, если процент соответствия 60% или больше
        return $percentageMatching >= 60;
    }

    //проверяем является ли сообщение спамом
    public function checkSpam($message, $clientID, $checkRate = false)
    {
        $normalizedTokens = explode(' ', $this->normalizer->normalize($message));
        // Сначала проверяем, не слишком ли похоже текущее сообщение на предыдущее
        if ($this->isDuplicate($normalizedTokens, $clientID)) {
            return ['is_spam' => true, 'reason' => 'duplicate'];
        }

        $lastMessageTimeKey = "lastMessageTime:$clientID";
        $lastMessageTime = $this->redis->get($lastMessageTimeKey);

        // Если проверка частоты включена и время с последнего сообщения меньше 3 сек, то это спам
        if ($checkRate && $lastMessageTime && (time() - (int)$lastMessageTime < 3)) {
            return ['is_spam' => true, 'reason' => 'check_rate'];
        }

        // Обновляем временную метку в редисе если сообщение не является спамом
        if (!$checkRate || ($lastMessageTime && (time() - (int)$lastMessageTime >= 3))) {
            $this->redis->set($lastMessageTimeKey, time());
        }

        //нормализуем сообщение
        $normalizedMessage = $this->normalizer->normalize($message);

        //проверяем на наличие слов из блоклиста
        foreach (explode(' ', $normalizedMessage) as $word) {
            if (in_array($word, $this->blockList)) {
                return ['is_spam' => true, 'reason' => 'block_list'];
            }
        }

        //проверяем на наличие имэйла
        if (filter_var($message, FILTER_VALIDATE_EMAIL)) {
            return ['is_spam' => true, 'reason' => 'block_list'];
        }

        //проверяем на наличие смешанных слов
        if (preg_match('/[a-zA-Z].*[а-яА-Я]|[а-яА-Я].*[a-zA-Z]/', $message)) {
            return ['is_spam' => true, 'reason' => 'mixed_words'];
        }

        //апдейтим время последнего сообщения
        $this->redis->set($lastMessageTimeKey, time());

        return ['is_spam' => false];
    }
}
