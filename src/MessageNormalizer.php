<?php

namespace src;

class MessageNormalizer
{
    private $stopWords;

    public function __construct($stopWordsPath)
    {
        $this->stopWords = file($stopWordsPath, FILE_IGNORE_NEW_LINES);
    }

    /**
     * Нормализация сообщения
     *
     * @param string $message
     * @return string
     */
    public function normalize(string $message): string
    {
        $message = mb_strtolower($message);
        $tokens = $this->tokenize($message);
        $tokens = $this->removeStopWords($tokens);
        $tokens = $this->removeNumericWords($tokens);
        sort($tokens);
        $tokens = array_filter($tokens);
        return implode(' ', $tokens);
    }

    /**
     * Токенизация сообщения
     *
     * @param string $message
     * @return array
     */
    private function tokenize(string $message): array
    {
        // Ищем в тексте имэйлы и отдельные слова
        $pattern = '#(\\S+@\\S+\\.\\S+)|[.,!?\[\\]()<>:;\\-\\n\'\\r\\s"/*|]+#';
        $tokens = preg_split($pattern, strtolower($message), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        // Убираем токены которые не являются отдельными словами или имэйлами
        return array_values(array_filter($tokens, function ($token) {
            return filter_var($token, FILTER_VALIDATE_EMAIL) || !preg_match('#^[.,!?\[\\]()<>:;\\-\\n\'\\r\\s"/*|]+$#', $token);
        }));
    }

    /**
     * Убирает стоп слова
     *
     * @param array $tokens
     * @return array
     */
    private function removeStopWords(array $tokens): array
    {
        return array_diff($tokens, $this->stopWords);
    }

    /**
     * Убирает слова состоящие из цифр
     *
     * @param array $tokens
     * @return array
     */
    private function removeNumericWords(array $tokens): array
    {
        return array_filter($tokens, function ($token) {
            return !is_numeric($token);
        });
    }
}
