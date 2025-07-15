<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/BitrixClient.php';
require_once __DIR__ . '/src/AmoClient.php';

// Получаем данные формы
$formData = [
    'name' => $_POST['name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'comment' => $_POST['comment'] ?? '',
];

$config = require __DIR__ . '/config.php';

try {
    // Отправка в Bitrix24
    $bitrixClient = new BitrixClient($config['Bitrix24']['webhook_url']);
    $bitrixResult = $bitrixClient->sendData($formData);
    
    // Отправка в amoCRM
    $amoClient = new AmoClient($config['amoCRM']);
    $amoResult = $amoClient->sendData($formData);
    
    // Перенаправление на страницу благодарности
    header("Location: thanks.html");
    exit;
} catch (Exception $e) {
    // Логирование ошибки
    error_log('Form handler error: ' . $e->getMessage());
    exit;
}