<?php
require_once __DIR__ . '/../src/MessageNormalizer.php';

use src\MessageNormalizer;

$normalizer = new MessageNormalizer(__DIR__ . '/../docs/stopwords.txt', __DIR__ . '/../docs/blocklist.txt');

$testMessage = "12345, 67890, а также другие числа";

$tokens = $normalizer->normalize($testMessage);

header('Content-type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'message' => 'Kek', 'normalized_text' => $tokens]);




