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
        $message = mb_strtolower($message);
        echo $message;
        $tokens = $this->tokenize($message);
        $tokens = $this->removeStopWords($tokens);
        $tokens = $this->removeNumericWords($tokens);
        sort($tokens);
        $tokens = array_filter($tokens);
        return $tokens;
    }

    //метод для разбивки сообщения на тоекены
    private function tokenize($message)
    {
        //ищем в тексте имэйлы и отдельные слова
        $pattern = '/(\\S+@\\S+\\.\\S+)|[\\.\\,\\!\\?\\[\\]\\(\\)\\<\\>\\:\\;\\-\\n\\\'\\r\\s\\"\\/\\*\\|]+/';
        $tokens = preg_split($pattern, strtolower($message), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        return array_values(array_filter($tokens, function ($token) {
            //убираем токены если они не являются словом или имэйлом
            return filter_var($token, FILTER_VALIDATE_EMAIL) || !preg_match('/^[\\.\\,\\!\\?\\[\\]\\(\\)\\<\\>\\:\\;\\-\\n\\\'\\r\\s\\"\\/\\*\\|]+$/', $token);
        }));
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
