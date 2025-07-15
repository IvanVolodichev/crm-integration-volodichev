<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Models\LeadModel;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Models\TagModel;
use Carbon\Carbon;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;

class AmoClient
{
    private $apiClient;
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiClient = new AmoCRMApiClient(
            $config['client_id'],
            $config['client_secret'],
            $config['redirect_uri']
        );
        
        $this->loadTokens();
    }

    private function loadTokens(): void
    {
        $tokenData = json_decode(file_get_contents($this->config['token_file']), true);
        $accessToken = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $tokenData['accessToken'],
            'refresh_token' => $tokenData['refreshToken'],
            'expires' => $tokenData['expires'],
        ]);

        $this->apiClient->setAccessToken($accessToken)
                       ->setAccountBaseDomain($tokenData['baseDomain']);
    }

    public function sendData(array $formData): array
    {
        $contact = $this->createContact($formData);
        $lead = $this->createLead($formData, $contact);
        $this->addNote($lead, $formData['comment']);
        
        return ['success' => true];
    }

    private function createContact(array $formData)
    {
        $contact = new ContactModel();
        $contact->setName($formData['name']);

        $phoneCustomFields = new CustomFieldsValuesCollection();

        // Создаем поле для телефона
        $phoneField = (new MultitextCustomFieldValuesModel())
            ->setFieldCode('PHONE');

        // Создаем и добавляем значение телефона
        $phoneField->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setValue($formData['phone'])  // Корректный номер телефона
                )
        );

        // Добавляем поле телефона в коллекцию кастомных полей
        $phoneCustomFields->add($phoneField);

        // Устанавливаем кастомные поля для контакта
        $contact->setCustomFieldsValues($phoneCustomFields);

        return $this->apiClient->contacts()->addOne($contact);
    }

    private function createLead(array $formData, $contact)
    {
        $lead = new LeadModel();
        $lead->setName('Заявка с сайта ' . Carbon::now())
             ->setContacts((new ContactsCollection())->add($contact));

        // добавляем тэг
        $tags = new TagsCollection();
        $tags->add((new TagModel())->setName('сайт'));
        $lead->setTags($tags);

        // добавляем источник
        $leadCustomFields = new CustomFieldsValuesCollection();

        $selectField = new SelectCustomFieldValuesModel();
        $selectField->setFieldId(808951);

        $selectValue = new SelectCustomFieldValueModel();
        $selectValue->setValue('Сайт');

        $selectValueCollection = new SelectCustomFieldValueCollection();
        $selectValueCollection->add($selectValue);

        $selectField->setValues($selectValueCollection);
        $leadCustomFields->add($selectField);

        $lead->setCustomFieldsValues($leadCustomFields);

        return $this->apiClient->leads()->addOne($lead);
    }

    private function addNote($lead, string $comment): void
    {
        $note = new \AmoCRM\Models\NoteType\CommonNote();
        $note->setEntityId($lead->getId())
             ->setText($comment);

        $notesCollection = new \AmoCRM\Collections\NotesCollection();
        $notesCollection->add($note);

        $this->apiClient->notes('leads')->add($notesCollection);
    }
}