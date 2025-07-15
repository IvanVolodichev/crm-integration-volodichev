<?php
require_once __DIR__ . '/vendor/autoload.php';

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Models\ContactModel;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Models\LeadModel;
use Carbon\Carbon;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Models\TagModel;

// 1. Настройки клиента
$clientId = 'a0faa7c2-3672-431e-aa0e-fc5e4f6d7acf';
$clientSecret = 'rg8eZvghpWqhQF1gzY3SkpYC7yrS2RGz7Wlp9X92Hjd5As2zrOLJ6PZTHr2OFM6n';
$redirectUri = 'http://cognitive.beget.tech/amo_auth.php';
// $redirectUri = 'http://localhost:8000/amo_auth.php';

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

// 2. Загружаем токены
$tokenData = json_decode(file_get_contents(__DIR__ . '/tokens.json'), true);

$accessToken = new AccessToken([
    'access_token' => $tokenData['accessToken'],
    'refresh_token' => $tokenData['refreshToken'],
    'expires' => $tokenData['expires'],
]);

$apiClient->setAccessToken($accessToken)
          ->setAccountBaseDomain($tokenData['baseDomain']);

// 3. Получаем данные из формы
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$comment = $_POST['comment'] ?? '';
$source = 'Сайт';

$contact = new ContactModel();
$contact->setName($name);

$phoneCustomFields = new CustomFieldsValuesCollection();

// Создаем поле для телефона
$phoneField = (new MultitextCustomFieldValuesModel())
    ->setFieldCode('PHONE');

// Создаем и добавляем значение телефона
$phoneField->setValues(
    (new MultitextCustomFieldValueCollection())
        ->add(
            (new MultitextCustomFieldValueModel())
                ->setValue($phone)  // Корректный номер телефона
        )
);

// Добавляем поле телефона в коллекцию кастомных полей
$phoneCustomFields->add($phoneField);

// Устанавливаем кастомные поля для контакта
$contact->setCustomFieldsValues($phoneCustomFields);

try {
    // Создаем контакт в amoCRM
    $contactModel = $apiClient->contacts()->addOne($contact);
} catch (AmoCRMApiException $e) {
    printError($e);
    die;
}

$leadsService = $apiClient->leads();

$lead = new LeadModel();

$lead->setName('Заявка с сайта ' . Carbon::now())
    ->setContacts(
        (new ContactsCollection())
            ->add(
                (new ContactModel())
                    ->setId($contactModel->getId())
            )
    );

// Добавляем тег к сделке
$tags = new TagsCollection();
$tags->add((new TagModel())->setName('сайт'));
$lead->setTags($tags);

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

try {
    // Создаем сделку
    $lead = $apiClient->leads()->addOne($lead);
} catch (AmoCRMApiException $e) {
    print($e);
    die;
}

$note = new CommonNote();
$note->setEntityId($lead->getId());
$note->setText($comment);

$notesCollection = new NotesCollection();
$notesCollection->add($note);

$leadNotesService = $apiClient->notes('leads');
$leadNotesService->add($notesCollection);

header("Location: ./thanks.html");
