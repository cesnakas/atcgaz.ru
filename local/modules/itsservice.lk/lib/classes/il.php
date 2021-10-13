<?php

namespace Its\Service;

use Bitrix\Main\Web\HttpClient;
use CFile;

class CIL extends CMain
{
  const ENTITY_FL_ID = 4700;
  const ENTITY_YL_ID = 4702;
  const ENTITY_IP_ID = 7788;

  public function __construct($dealId)
  {
    $dealId = intval($dealId);
    if ($dealId <= 0) return false;
    $this->getDealData($dealId);
  }

  private function getDealData($dealId)
  {
    $arParams = [
      'halt' => false,
      'cmd' => [
        "get_deal" => 'crm.deal.get?' . http_build_query(["id" => $dealId]),
        "get_contact" => 'crm.contact.get?' . http_build_query(["id" => '$result[get_deal][CONTACT_ID]']),
      ],
    ];
    $res = CMain::getDataHttp('batch', $arParams);
    if(!empty($res['result']['result']['get_deal'])) {
      $arDeal = $res['result']['result']['get_deal'];
    } else {
      die();
    }

    if(!empty($res['result']['result']['get_contact'])) {
      $arContact = $res['result']['result']['get_contact'];
    } else {
      $this->sendErrorNotificationInDeal($dealId, 'Не найден контакт для отправки в ИЛ');
      die();
    }

    $b24Fields = [
      'UF_CRM_1566246595',
      'UF_CRM_1585246934',
      'UF_CRM_1585246993',
      'UF_CRM_5DE792490D007',
      'UF_CRM_1585247288',
      'UF_CRM_1585247384',
      'UF_CRM_1585247401',
      'UF_CRM_1585247422',
      'UF_CRM_1585247446',
      'UF_CRM_1585247587',
      'UF_CRM_1585247619',
      'UF_CRM_1585247662',
      'UF_CRM_1585247692',
      'UF_CRM_1585247787',
      'UF_CRM_1585247911',
      'TITLE',
      'UF_CRM_1585249358',
      'UF_CRM_5EC4EFA685D26',
    ];

    $company = $arDeal['COMPANY_ID'];
    $listsReestrElementId = $arDeal['UF_CRM_1615788283'];
    $params = [
      'id' => $dealId,
      'VIN' => $arDeal['UF_CRM_1585246797'],
      'additional_fields' => [],
    ];

    foreach ($b24Fields as $key => $field) {
      if($key <> 17) {
        $params['additional_fields'][] = $arDeal[$field];
      } else {
        if($arDeal[$field] == 6062) {
          $params['additional_fields'][] = 'метан КПГ';
        } else {
          $params['additional_fields'][] = 'СНГ';
        }
      }



    }

    $params['address'] = $arContact['UF_CRM_1575312507'];
    $params['phone'] = $this->getContactPhone($arContact);

    if (!empty($arDeal['COMPANY_ID'])) {
      $company = $this->getCompanyName($company);
      $params['company'] = $company;
    } else {
      $arContact = $this->getContactName($arContact);
      $params['contact'] = $arContact;
    }

    $arFileFields = [];
    switch ($arDeal['UF_CRM_1616438611']) {
      case self::ENTITY_FL_ID:
        $arFileFields = $this->getFLFilesField();
        break;
      case self::ENTITY_IP_ID:
        $arFileFields = $this->getIPFilesField();
        break;
      case self::ENTITY_YL_ID:
        $arFileFields = $this->getYlFilesField();
        break;
      default:
        $this->sendErrorNotificationInDeal($dealId, 'Не найдено значение поля "Тип собственника ТС" для отправки в ИЛ');
        die();
    }

    $token = CMain::getAccessTokenBitrix24();

    foreach ($arDeal as $key => $value) {
      if (isset($arFileFields[$key])) {
        if (isset($value[0]['id'])) $value = $value[0];
        if (!$value['downloadUrl']) continue;

        $urlFile = 'https://its-online.bitrix24.ru' . $value['downloadUrl'] . '&auth=' . $token;
        $httpClient = new HttpClient();
        $filename = $httpClient->head($urlFile)->getFilename();

        $arFiles[] = [
          'name' => $arFileFields[$key] . '.' . pathinfo($filename, PATHINFO_EXTENSION),
          'url' => $urlFile,
        ];
      }
    }

    if (empty($arFiles)) {
      $this->sendErrorNotificationInDeal($dealId, 'Не найдены файлы для отправки в ИЛ');
      die();
    }

    $params['files'] = $arFiles;
    $params['text_conclusion_il'] = $arDeal['UF_CRM_1618224799450'];

    $resultId = $this->send1C($params);

    $f = fopen($_SERVER['DOCUMENT_ROOT']."/il.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r(date('результат отправки'), 1));
    fwrite($f, print_r($resultId, 1));
    fclose($f);

    if (intval($resultId) > 0) {
      $arParams = [
        'halt' => false,
        'cmd' => [
          "deal_update" => 'crm.deal.update?'
            . http_build_query([
              "id" => $dealId,
              'fields' => [
                'UF_CRM_1616339268' => true,        //Документы отправлены в ИЛ
                'UF_CRM_1618380448137' => intval($resultId),        //ID отправки документов в ИЛ
                'UF_CRM_1615057108744' => date('d.m.Y'),        //Дата заявки в Испытательную Лабораторию
              ]
            ]),
          "send_notify" => 'im.notify.system.add?'
            . http_build_query([
              'USER_ID' => $arDeal['ASSIGNED_BY_ID'],
              'MESSAGE' => $arDeal['TITLE'] . ' - документы получены испытательной лабораторией'
            ]),
          "send_chat" => 'im.message.add?'
            . http_build_query([
              'MESSAGE' => $arDeal['TITLE'] . ' - документы получены испытательной лабораторией',
              'DIALOG_ID' => 'chat366',
              'SYSTEM' => 'Y',
            ]),
        ],
      ];
      if ($listsReestrElementId > 0) {
        $arParams['cmd']['list_element_update'] = 'lists.element.update?'
          . http_build_query([
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '38',
            'ELEMENT_ID' => $listsReestrElementId,
            'FIELDS' => [
              'PROPERTY_358' => date('d.m.Y')
            ]
          ]);
      }
      $res = CMain::getDataHttp('batch', $arParams);
    }
  }

  private function sendErrorNotificationInDeal($dealId, $errorMessage)
  {
    $arParams = [
      'fields' => [
        'ENTITYTYPEID' => 2,
        'POST_TITLE' => 'Ошибка отправки в ИЛ',
        'MESSAGE' => $errorMessage,
        'ENTITYID' => $dealId
      ]
    ];

    CMain::getDataHttp('crm.livefeedmessage.add', $arParams);
  }

  private function send1C($params)
  {
    $f = fopen($_SERVER['DOCUMENT_ROOT']."/il.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r($params, 1));
    fclose($f);
    $query = json_encode($params);
    $c = curl_init('http://reg.max-gas.ru/1C/hs/get/setdoc');

    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($c, CURLOPT_USERPWD, 'admin' . ":" . '123admin123');
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, $query);
    $response = curl_exec($c);

    $f = fopen($_SERVER['DOCUMENT_ROOT']."/il.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r($response, 1));
    fclose($f);

    return $response;
  }

  private function getContactPhone($contact)
  {
    $phones = $contact['PHONE'];
    foreach ($phones as $phone) {
      if ($phone['VALUE_TYPE'] == 'WORK') return $phone['VALUE'];
    }

    return $phones[0]['VALUE'];
  }

  private function getCompanyName($id)
  {
    $company = CMain::getDataHttp('crm.company.get', ['id' => $id])['result'];
    $company = $company['TITLE'];
    return $company;
  }

  private function getContactName($contact)
  {
    $name = $contact['NAME'];
    $name2 = $contact['SECOND_NAME'];
    $name3 = $contact['LAST_NAME'];
    $contact = $name3 . ' ' . $name . ' ' . $name2;
    return $contact;
  }

  private function getYlFilesField(): array
  {
    return [
      'UF_CRM_1605248226775' => 'ЗАЯВКА',
      'UF_CRM_1616178147253' => 'Паспорт представителя',
      'UF_CRM_1615053286137' => 'Доверенность',
      'UF_CRM_1575482284' => 'СТС',
      'UF_CRM_1575482164' => 'ПТС',
      'UF_CRM_1616093122972' => 'Реквизиты',
    ];
  }

  private function getIPFilesField(): array
  {
    return [
      'UF_CRM_1605248226775' => 'ЗАЯВКА',
      'UF_CRM_1616178147253' => 'Паспорт представителя',
      'UF_CRM_1615053286137' => 'Доверенность',
      'UF_CRM_1575482284' => 'СТС',
      'UF_CRM_1575482164' => 'ПТС',
      'UF_CRM_1616093122972' => 'Реквизиты',
      'UF_CRM_1614175606698' => 'Паспорт собственника',
    ];
  }

  private function getFLFilesField(): array
  {
    return [
      'UF_CRM_1605248226775' => 'ЗАЯВКА',
      'UF_CRM_1614175606698' => 'Паспорт собственника',
      'UF_CRM_1615053286137' => 'Доверенность',
      'UF_CRM_1575482284' => 'СТС',
      'UF_CRM_1575482164' => 'ПТС',
      'UF_CRM_1616178147253' => 'Паспорт представителя',
      'UF_CRM_1616086360932' => 'СНИЛС',
    ];
  }

  public static function updloadFile($dealId, $name, $url)
  {
    $file = file_get_contents($url);
    $file = base64_encode($file);

    $today = new \DateTime();

    $arFieldsUpdate = [
      'ID' => $dealId,
      'FIELDS' => [
        'UF_CRM_1575324942' => [
          'fileData' => [
            $name,
            $file
          ]
        ],
        'UF_CRM_1585249812' => $today->format('Y-m-d H:i:s'),
      ]
    ];

    $resDealUpdate = CMain::getDataHttp('crm.deal.update', $arFieldsUpdate);

    $arDeal = CMain::getDataHttp('crm.deal.get', ['id' => $dealId])['result'];

    if (empty($arDeal)) die();

    $sendChatParam = [
      'MESSAGE' => $arDeal['TITLE'].' - Скан-копия Предварительной Технической Экспертизы получена',
      'DIALOG_ID' => 'chat366',
      'SYSTEM' => 'Y',
    ];
    CMain::getDataHttp('im.message.add', $sendChatParam);

    $notifyParam = [
      'USER_ID' => $arDeal['ASSIGNED_BY_ID'],
      'MESSAGE' => $arDeal['TITLE'].' - Скан-копия Предварительной Технической Экспертизы получена'
    ];
    CMain::getDataHttp('im.notify.system.add', $notifyParam);
  }
}