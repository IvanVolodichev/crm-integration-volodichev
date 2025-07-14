<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessTokenInterface;


// Конфигурация
$clientId = 'a0faa7c2-3672-431e-aa0e-fc5e4f6d7acf';
$clientSecret = 'rg8eZvghpWqhQF1gzY3SkpYC7yrS2RGz7Wlp9X92Hjd5As2zrOLJ6PZTHr2OFM6n';
$redirectUri = 'http://cognitive.beget.tech/amo_auth.php';

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

session_start();

// Генерируем случайный state-параметр
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;

// Переход пользователя по ссылке для авторизации
if (!isset($_GET['code'])) {
    $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
        'state' => $state,
    ]);

    echo "<a href='{$authorizationUrl}'>Авторизоваться в amoCRM</a>";
    exit;
}

// Обработка кода после авторизации
try {
    /** @var AccessTokenInterface $accessToken */
    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

    // Сохраняем токены
    file_put_contents(__DIR__ . '/tokens.json', json_encode([
        'accessToken' => $accessToken->getToken(),
        'refreshToken' => $accessToken->getRefreshToken(),
        'expires' => $accessToken->getExpires(),
        'baseDomain' => $apiClient->getAccountBaseDomain(),
    ]));

    echo "Успешно авторизовано! Токены сохранены.";
} catch (Exception $e) {
    echo "Ошибка авторизации: " . $e->getMessage();
}