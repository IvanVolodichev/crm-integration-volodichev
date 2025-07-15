<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Carbon\Carbon;

class BitrixClient
{
    private $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function sendData(array $formData): array
    {
        $contact = $this->addContact($formData);
        $deal = $this->addDeal($formData, $contact['result']);
        $this->addComment($formData, $deal['result']);
        
        return ['success' => true];
    }

    private function callApi(string $method, array $data = null): array
    {
        $queryUrl = $this->webhookUrl . $method;
        $queryData = $data ? http_build_query($data) : '';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($result, true) ?: [];
    }

    private function addContact(array $formData): array
    {
        return $this->callApi('crm.contact.add', [
            'FIELDS' => [
                'NAME' => $formData['name'],
                'PHONE' => [['VALUE' => $formData['phone'], 'VALUE_TYPE' => 'WORK']]
            ]
        ]);
    }

    private function addDeal(array $formData, int $contactId): array
    {
        return $this->callApi('crm.deal.add', [
            'FIELDS' => [
                'TITLE' => 'Заявка с сайта ' . Carbon::now(),
                'CONTACT_ID' => $contactId,
                'UF_CRM_1752569895162' => 47,
            ],
            'PARAMS' => ['REGISTER_SONET_EVENT' => 'N'],
        ]);
    }

    private function addComment(array $formData, int $dealId): array
    {
        return $this->callApi('crm.timeline.comment.add', [
            'fields' => [
                'ENTITY_ID' => $dealId,
                'ENTITY_TYPE' => 'deal',
                'COMMENT' => $formData['comment'],
                'AUTHOR_ID' => $contactId ?? 0,
            ]
        ]);
    }
}