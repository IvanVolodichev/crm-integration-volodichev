<?php

return [
    'amoCRM' => [
        'client_id' => 'a0faa7c2-3672-431e-aa0e-fc5e4f6d7acf',
        'client_secret' => 'rg8eZvghpWqhQF1gzY3SkpYC7yrS2RGz7Wlp9X92Hjd5As2zrOLJ6PZTHr2OFM6n',
        'redirectUri' => 'http://cognitive.beget.tech/amo_auth.php', //для хоста
        // 'redirect_uri' => 'http://localhost:8000/amo_auth.php', // для локалки
        'token_file' => __DIR__ . '/tokens.json',
    ],

    'Bitrix24' => [
        'webhook_url' => 'https://b24-0ixgr6.bitrix24.ru/rest/1/pmw63y7f5tzjubze/',
    ],
];