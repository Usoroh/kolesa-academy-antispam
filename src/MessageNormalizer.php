<?php

namespace src;

class MessageNormalizer
{
    private $stopWords;
    private $blockList;

    public function __construct($stopWordsPath, $blockListPath)
    {
        $this->stopWords = file($stopWordsPath, FILE_IGNORE_NEW_LINES);
        $this->blockList = file($blockListPath, FILE_IGNORE_NEW_LINES);
    }

    //Нормализуем сообщение
    public function normalize($message)
    {
        $tokens = $this->tokenize($message);
        $tokens = $this->removeStopWords($tokens);
        $tokens = $this->removeNumericWords($tokens);
        sort($tokens);
        return $tokens;
    }

    //метод для разбивки сообщения на тоекены
    private function tokenize($message)
    {
        $pattern = '/[\\.\\,\\!\\?\\[\\]\\(\\)\\<\\>\\:\\;\\-\\n\\\'\\r\\s\\"\\/\\*\\|]+/';
        return preg_split($pattern, strtolower($message));
    }

    //убираем стоп слова
    private function removeStopWords($tokens)
    {
        return array_diff($tokens, $this->stopWords);
    }

    //убираем слова состоящие из цифр
    private function removeNumericWords($tokens)
    {
        return array_filter($tokens, function ($token) {
            return !is_numeric($token);
        });
    }

    //проверям в блок листе ли слово
    public function isWordInBlockList($word)
    {
        return in_array($word, $this->blockList);
    }
}
