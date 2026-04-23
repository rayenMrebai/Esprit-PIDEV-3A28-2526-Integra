<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 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',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'google/gemma-3-4b-it:free',
    'messages' => [['role' => 'user', 'content' => 'dis bonjour']],
    'max_tokens' => 10
]));
$response = curl_exec($ch);
$error = curl_error($ch);
echo 'RESPONSE: ' . $response . PHP_EOL;
echo 'ERROR: ' . $error . PHP_EOL;
curl_close($ch);