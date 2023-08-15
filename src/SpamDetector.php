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

    //проверяем является ли сообщение спамом
    public function checkSpam($message, $clientID, $checkRate = false)
    {
        $lastMessageTimeKey = "lastMessageTime:{$clientID}";

        //проверяем время отправки последнего сообщения. Если меньше 3 сек - спам
        if ($checkRate) {
            $lastMessageTime = $this->redis->get($lastMessageTimeKey);
            if ($lastMessageTime && (time() - $lastMessageTime < 3)) {
                return ['is_spam' => true, 'reason' => 'check_rate'];
            }
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
