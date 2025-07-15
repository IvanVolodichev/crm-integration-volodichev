<?php
require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

// $webhook_url = 'https://b24-0ixgr6.bitrix24.ru/rest/1/pmw63y7f5tzjubze/';
$source = 'Сайт';

$formData = [
    'name' => $_POST['name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'comment' => $_POST['comment'] ?? '',
];

function sendDataToBitrix($method, $data = null)
{
    $query_url = 'https://b24-0ixgr6.bitrix24.ru/rest/1/pmw63y7f5tzjubze/' . $method;
    
    $data != null ? $query_data = http_build_query($data) : ''; 

    $curl = curl_init();

    curl_setopt_array($curl,[
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $query_url,
        CURLOPT_POSTFIELDS => $query_data,
    ]);

    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}

// Добавляем контакт
$contact = sendDataToBitrix('crm.contact.add',[
    'FIELDS' => [
        'NAME' => $formData['name'],
        'PHONE' => [
            [
                'VALUE' => $formData['phone'],
                'VALUE_TYPE' => 'WORK',
            ],
        ]
    ],
]);

// Добавляем сделку
$deal = sendDataToBitrix('crm.deal.add', [
    'FIELDS' => [
        'TITLE' => 'Заявка с сайта ' . Carbon::now(),
        'CONTACT_ID' => $contact['result'],
        'UF_CRM_1752569895162' => 47,
        ],
    'PARAMS' => [
        'REGISTER_SONET_EVENT' => 'N',
    ],
]);

// Добавляем комментарий
$comment = sendDataToBitrix('crm.timeline.comment.add',
    [
        'fields' => [
            'ENTITY_ID' => $deal['result'],
            'ENTITY_TYPE' => 'deal',
            'COMMENT' => $formData['comment'],
            'AUTHOR_ID' => $contact['result'],
        ]
    ]
);