<?php
require_once __DIR__ . '/vendor/autoload.php';

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessTokenInterface;

// Конфигурация
$clientId = '9152fb82-9431-4f5b-96c1-e6ccf6fe3120';
$clientSecret = 'oHzR6klMrJdhRO3tSQPmIvQmITZkuOPx8sSNqXosdoig4jreTWrwIq2g7KnTYkQf';
$redirectUri = 'http://cognitive.beget.tech/amo_auth.php';

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

// Переход пользователя по ссылке для авторизации
if (!isset($_GET['code'])) {
    $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
    ]);

    echo "<a href='{$authorizationUrl}'>Авторизоваться в amoCRM</a>";
    exit;
}

// Обработка кода после авторизации
try {
    /** @var AccessTokenInterface $accessToken */
    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

    // Сохраняем токены (рекомендуется в БД или файле)
    file_put_contents(__DIR__ . '/tokens.json', json_encode([
        'accessToken' => $accessToken->getToken(),
        'refreshToken' => $accessToken->getRefreshToken(),
        'expires' => $accessToken->getExpires(),
    ]));

    echo "Успешно авторизовано!";
} catch (Exception $e) {
    echo "Ошибка авторизации: " . $e->getMessage();
}