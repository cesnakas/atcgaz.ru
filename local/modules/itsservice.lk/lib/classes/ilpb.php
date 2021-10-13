<?php

namespace Its\Service;

use Bitrix\Main\Web\HttpClient;

class CILPB extends CMain
{
  private $token;

  public function __construct($dealId)
  {
    $dealId = intval($dealId);
    if ($dealId <= 0) return false;
    $this->token = CMain::getAccessTokenBitrix24();
    $this->getDealData($dealId);
  }

  private function getDealData($dealId)
  {
    $arResult = [
      'ID' => $dealId,
    ];
    $arParams = [
      'halt' => false,
      'cmd' => [
        "get_deal" => 'crm.deal.get?' . http_build_query(["id" => $dealId]),
        "get_company" => 'crm.company.get?' . http_build_query(["id" => '$result[get_deal][COMPANY_ID]']),
        "get_fields_list" => 'crm.deal.userfield.list?' . http_build_query(["filter" => ['FIELD_NAME' => 'UF_CRM_1616438611']]),
        "get_contact" => 'crm.contact.get?' . http_build_query(["id" => '$result[get_deal][CONTACT_ID]']),
      ],
    ];
    $res = CMain::getDataHttp('batch', $arParams);
    if(!empty($res['result']['result']['get_deal'])) {
      $arDeal = $res['result']['result']['get_deal'];
    } else {
      die();
    }

    if(!empty($res['result']['result']['get_company'])) {
      $arCompany = $res['result']['result']['get_company'];
    }

    if(!empty($res['result']['result']['get_contact'])) {
      $arContact = $res['result']['result']['get_contact'];
    }

    $arFieldList = [];
    if(!empty($res['result']['result']['get_fields_list'])) {
      $arFieldList = $res['result']['result']['get_fields_list'];
    }

    $arFileName = $this->getFileName();

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

    foreach ($b24Fields as $field)
      $arResult['additional_fields'][] = $arDeal[$field];

    $arResult['VIN'] = $arDeal['UF_CRM_1585246797'];


    $arResult['address'] = $arContact['UF_CRM_1575312507'];
    $arResult['phone'] = $this->getContactPhone($arContact);

    $company = $arDeal['COMPANY_ID'];
    if (!empty($arDeal['COMPANY_ID'])) {
      $company = $this->getCompanyName($company);
      $arResult['company'] = $company;
    } else {
      $arContact = $this->getContactName($arContact);
      $arResult['contact'] = $arContact;
    }

    $arResult['text_conclusion_il'] = $arDeal['UF_CRM_1618224799450'];

    foreach($arDeal as $key => $value) {
      switch ($key) {
        case 'UF_CRM_1623223670':   //Текст для ПБ
          $arResult['UF_CRM_1623223670'] = $value;
          break;
        case 'UF_CRM_1614175606698':    //Паспорт собственника ТС
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1614175606698'], $value));
          break;
        case 'UF_CRM_1616178147253':    //Паспорт представителя собственника ТС
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1616178147253'], $value);
          break;
        case 'UF_CRM_1615053286137':    //Доверенность для представления интересов в ГИБДД
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1615053286137'], $value);
          break;
        case 'UF_CRM_1616093122972':    //Реквизиты организации в формате PDF
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1616093122972'], $value);
          break;
        case 'UF_CRM_1585251039':   //Снаряженная масса ТС после монтажа
          $arResult['UF_CRM_1585251039'] = $value;
          break;
        case 'UF_CRM_1623394220040':   //Система питания
          $arResult['UF_CRM_1623394220040'] = $value;
          break;
        case 'UF_CRM_1623393760':   //Дополнительное оборудование
          $arResult['UF_CRM_1623393760'] = $value;
          break;
        case 'UF_CRM_1616438611':   //Тип собственника ТС
          $arResult['UF_CRM_1616438611'] = $this->getListValue('UF_CRM_1616438611', $value, $arFieldList);
          break;
        case 'UF_CRM_1605248226775':   //Заявка в Испытательную Лабораторию в формате PDF
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1605248226775'], $value);
          break;
        case 'UF_CRM_1585423171':   //№ документа ПТЭ
          $arResult['UF_CRM_1585423171'] = $value;
          break;
        case 'UF_CRM_1621324492':   //Разрешение ГИБДД на внесение изменений в конструкцию ТС
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1621324492'], $value));
          break;
        case 'UF_CRM_1605304592458':   //№ Разрешения ГИБДД
          $arResult['UF_CRM_1605304592458'] = $value;
          break;
        case 'UF_CRM_1623315747':   //Дата Разрешения ГИБДД
          $arResult['UF_CRM_1623315747'] = $value;
          break;
        case 'UF_CRM_1575323807':   //Скан-копия заявления-декларации (ЗД)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1575323807'], $value);
          break;
        case 'UF_CRM_1619517442464':   //№ Декларации
          $arResult['UF_CRM_1619517442464'] = $value;
          break;
        case 'UF_CRM_1623320727':   //Дата оформления Декларации
          $arResult['UF_CRM_1623320727'] = $value;
          break;
        case 'UF_CRM_1606485376580':   //СКАН-КОПИЯ свидетельства о проведении испытаний (форма 207)
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1606485376580'], $value));
          break;
        case 'UF_CRM_1623317157':   //№ Свидетельства
          $arResult['UF_CRM_1623317157'] = $value;
          break;
        case 'UF_CRM_1623317317':   //Дата оформления Свидетельства
          $arResult['UF_CRM_1623317317'] = $value;
          break;
        case 'UF_CRM_1617123588':   //Модель блока управления
          $arResult['UF_CRM_1617123588'] = $value;
          break;
        case 'UF_CRM_1617121257724':   //Серийный № блока управления
          $arResult['UF_CRM_1617121257724'] = $value;
          break;
        case 'UF_CRM_1617123633':   //Модель редуктора
          $arResult['UF_CRM_1617123633'] = $value;
          break;
        case 'UF_CRM_1617121395792':   //Серийный номер редуктора
          $arResult['UF_CRM_1617121395792'] = $value;
          break;
        case 'UF_CRM_1617123615':   //Модель мультиклапана / вентиля
          $arResult['UF_CRM_1617123615'] = $value;
          break;
        case 'UF_CRM_1617121324413':   //Серийный номер мультиклапана / вентиля
          $arResult['UF_CRM_1617121324413'] = $value;
          break;
        case 'UF_CRM_1613495442452':   //Фото ПОСЛЕ передняя часть ТС (с читаемым гос номером)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1613495442452'], $value);
          break;
        case 'UF_CRM_1613495546380':   //Фото ПОСЛЕ слева (по ходу движения ТС)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1613495546380'], $value);
          break;
        case 'UF_CRM_1613495516381':   //Фото ПОСЛЕ задняя часть (с читаемым гос номером)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1613495516381'], $value);
          break;
        case 'UF_CRM_1613495468933':   //Фото ПОСЛЕ справа (по ходу движения ТС)
          $arResult['FILES']['UF_CRM_1613495468933'] = $this->getFile($arFileName['UF_CRM_1613495468933'], $value);
          break;
        case 'UF_CRM_1623322596':   //Фото: Информационная табличка баллона
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322596'], $value);
          break;
        case 'UF_CRM_1602764217225':   //Фото:  Баллон (месторасположение и способ крепления)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1602764217225'], $value);
          break;
        case 'UF_CRM_1623322662':   //Фото: Мультиклапан - тип, наличие ЭМК
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322662'], $value);
          break;
        case 'UF_CRM_1602763312701':   //Фото: Место установки заправочного устройства
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1602763312701'], $value);
          break;
        case 'UF_CRM_1623322751':   //Фото: Моторный отсек (общий вид)
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322751'], $value);
          break;
        case 'UF_CRM_1623322827':   //Фото: Редуктор-испаритель
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322827'], $value);
          break;
        case 'UF_CRM_1623322861':   //Фото: Электронный блок управления
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322861'], $value);
          break;
        case 'UF_CRM_1623322893':   //Фото: Форсунки
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322893'], $value);
          break;
        case 'UF_CRM_1623322944':   //Фото: Фильтр
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322944'], $value);
          break;
        case 'UF_CRM_1623322989':   //Фото: Датчик давления/температуры
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623322989'], $value);
          break;
        case 'UF_CRM_1606485425330':   //СКАН-КОПИЯ заполненного паспорта на баллон
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1606485425330'], $value));
          break;
        case 'UF_CRM_1623074927':   //Скан сертификата баллона
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1623074927'], $value);
          break;
        case 'UF_CRM_1621326020':   //Сертификат на установленное ГБО
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1621326020'], $value));
          break;
        case 'UF_CRM_1575324942':   //ПЗ
          $arResult['FILES'] = array_merge($arResult['FILES'], $this->getMultipleFile($arFileName['UF_CRM_1575324942'], $value));
          break;
      }
    }

    foreach($arCompany as $key => $value) {
      switch ($key) {
        case 'UF_CRM_1585245202':    //Полное наименование организации
          $arResult['UF_CRM_1585245202'] = $value;
          break;
        case 'UF_CRM_1592462881003':    //Юридический адрес (полный)
          $arResult['UF_CRM_1592462881003'] = $value;
          break;
        case 'UF_CRM_1603137217967':    //Скан сертификата
          $arResult['FILES'][] = $this->getFile($arFileName['UF_CRM_1603137217967'], $value);
          break;
        case 'UF_CRM_1585511477':    //№ сертификата соответствия ДЦ
          $arResult['UF_CRM_1585511477'] = $value;
          break;
        case 'UF_CRM_1585511581':    //Дата выдачи сертификата соответствия ДЦ
          $arResult['UF_CRM_1585511581'] = $value;
          break;
        case 'UF_CRM_1585940752':    //№ Сертификата газовой системы (Производителя)
          $arResult['UF_CRM_1585940752'] = $value;
          break;
        case 'UF_CRM_1585511845':    //Дата выдачи сертификата соответствия газовой системы
          $arResult['UF_CRM_1585511845'] = $value;
          break;
        case 'UF_CRM_1585940865':    //№ Сертификата газового баллона (Производителя)
          $arResult['UF_CRM_1585940865'] = $value;
          break;
        case 'UF_CRM_1585512016':    //Дата выдачи сертификата соответствия газового баллона
          $arResult['UF_CRM_1585512016'] = $value;
          break;
      }
    }

    $arResult['FILES'] = array_values($arResult['FILES']);
    $resultId = $this->send1C($arResult);

    $f = fopen($_SERVER['DOCUMENT_ROOT']."/ilpb.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r('Это отсылка на пб', 1));
    fwrite($f, print_r($resultId, 1));
    fwrite($f, print_r($arResult, 1));
    fclose($f);

//    $arParams = [
//      'halt' => false,
//      'cmd' => [
//        "send_notify" => 'im.notify.system.add?'
//          . http_build_query([
//            'USER_ID' => $arDeal['ASSIGNED_BY_ID'],
//            'MESSAGE' => $arDeal['TITLE'] . ' - заявка не отправлена ввиду применения ГОСТ 33670-2015'
//          ]),
//        "send_chat" => 'im.message.add?'
//          . http_build_query([
//            'MESSAGE' => $arDeal['TITLE'] . ' - заявка не отправлена ввиду применения ГОСТ 33670-2015',
//            'DIALOG_ID' => 'chat366',
//            'SYSTEM' => 'Y',
//          ]),
//      ],
//    ];
//    $res = CMain::getDataHttp('batch', $arParams);

    if (intval($resultId) > 0) {
      $arParams = [
        'halt' => false,
        'cmd' => [
          "deal_update" => 'crm.deal.update?'
            . http_build_query([
              "id" => $dealId,
              'fields' => [
                'UF_CRM_1623993740049' => true,        //Документы отправлены в ИЛ ПБ
                'STAGE_ID' => 'C4:8',
                'UF_CRM_1623994415' => intval($resultId),        //ID отправки документов в ИЛ
              ]
            ]),
          "send_notify" => 'im.notify.system.add?'
            . http_build_query([
              'USER_ID' => $arDeal['ASSIGNED_BY_ID'],
              'MESSAGE' => $arDeal['TITLE'] . ' - документы на ПБ получены испытательной лабораторией'
            ]),
          "send_chat" => 'im.message.add?'
            . http_build_query([
              'MESSAGE' => $arDeal['TITLE'] . ' - документы на ПБ получены испытательной лабораторией',
              'DIALOG_ID' => 'chat366',
              'SYSTEM' => 'Y',
            ]),
        ],
      ];
      $res = CMain::getDataHttp('batch', $arParams);
    }
  }

  private function getMultipleFile($fileName, $arValue): array
  {
    $result = [];
    foreach ($arValue as $item) {
      if (!$item['downloadUrl']) continue;
      $urlFile = 'https://its-online.bitrix24.ru' . $item['downloadUrl'] . '&auth=' . $this->token;
      $httpClient = new HttpClient();
      $filename = $httpClient->head($urlFile)->getFilename();

      $result[] = [
        'name' => $fileName . '.' . pathinfo($filename, PATHINFO_EXTENSION),
        'url' => $urlFile,
      ];
    }

    return $result;
  }

  private function getFile($fileName, $item): array
  {
    if (!$item['downloadUrl']) return [];
    $urlFile = 'https://its-online.bitrix24.ru' . $item['downloadUrl'] . '&auth=' . $this->token;
    $httpClient = new HttpClient();
    $filename = $httpClient->head($urlFile)->getFilename();

    return [
      'name' => $fileName . '.' . pathinfo($filename, PATHINFO_EXTENSION),
      'url' => $urlFile,
    ];
  }

  private function getListValue($code, $value, $arValue)
  {
    foreach($arValue as $arItem) {
      if($arItem['FIELD_NAME'] == $code) {
        foreach($arItem['LIST'] as $item) {
          if($item['ID'] == $value) {
            return $item['VALUE'];
          }
        }
      }
    }

    return false;
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

//    CMain::getDataHttp('crm.livefeedmessage.add', $arParams);
  }

  private function send1C($params)
  {
    $f = fopen($_SERVER['DOCUMENT_ROOT']."/ilpb.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r($params, 1));
    fclose($f);
    $query = json_encode($params, JSON_OBJECT_AS_ARRAY);
    $c = curl_init('http://reg.max-gas.ru/1C/hs/get/setdoc_p');

    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($c, CURLOPT_USERPWD, 'admin' . ":" . '123admin123');
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, $query);
    $response = curl_exec($c);

    $f = fopen($_SERVER['DOCUMENT_ROOT']."/ilpb.txt", "a");
    fwrite($f, print_r(date('d-m-Y'), 1));
    fwrite($f, print_r($response, 1));
    fclose($f);

    return $response;
  }

  public function getFileName(): array
  {
    return [
      'UF_CRM_1614175606698' => 'Паспорт собственника ТС',
      'UF_CRM_1616178147253' => 'Паспорт представителя собственника ТС',
      'UF_CRM_1615053286137' => 'Доверенность для представления интересов в ГИБДД',
      'UF_CRM_1616093122972' => 'Реквизиты организации в формате PDF',
      'UF_CRM_1605248226775' => 'Заявка в Испытательную Лабораторию в формате PDF',
      'UF_CRM_1621324492' => 'Разрешение ГИБДД на внесение изменений в конструкцию ТС',
      'UF_CRM_1575323807' => 'Скан копия заявления-декларации ЗД',
      'UF_CRM_1606485376580' => 'СКАН КОПИЯ свидетельства о проведении испытаний форма 207',
      'UF_CRM_1613495442452' => 'Фото ПОСЛЕ передняя часть ТС с читаемым гос номером',
      'UF_CRM_1613495546380' => 'Фото ПОСЛЕ слева по ходу движения ТС',
      'UF_CRM_1613495516381' => 'Фото ПОСЛЕ задняя часть с читаемым гос номером',
      'UF_CRM_1613495468933' => 'Фото ПОСЛЕ справа по ходу движения ТС',
      'UF_CRM_1623322596' => 'Фото Информационная табличка баллона',
      'UF_CRM_1602764217225' => 'Фото  Баллон месторасположение и способ крепления',
      'UF_CRM_1623322662' => 'Фото Мультиклапан тип, наличие ЭМК',
      'UF_CRM_1602763312701' => 'Фото Место установки заправочного устройства',
      'UF_CRM_1623322751' => 'Фото Моторный отсек общий вид',
      'UF_CRM_1623322827' => 'Фото Редуктор испаритель',
      'UF_CRM_1623322861' => 'Фото Электронный блок управления',
      'UF_CRM_1623322893' => 'Фото Форсунки',
      'UF_CRM_1623322944' => 'Фото Фильтр',
      'UF_CRM_1623322989' => 'Фото Датчик давлениятемпературы',
      'UF_CRM_1606485425330' => 'СКАН КОПИЯ заполненного паспорта на баллон',
      'UF_CRM_1623074927' => 'Скан сертификата баллона',
      'UF_CRM_1621326020' => 'Сертификат на установленное ГБО',
      'UF_CRM_1603137217967' => 'Скан сертификата',
      'UF_CRM_1575324942' => 'Предварительное заключение',
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
        'STAGE_ID' => 'C4:1',
        'UF_CRM_1623309968' => [
          'fileData' => [
            $name,
            $file
          ]
        ],
      ]
    ];

    $resDealUpdate = CMain::getDataHttp('crm.deal.update', $arFieldsUpdate);

    $arDeal = CMain::getDataHttp('crm.deal.get', ['id' => $dealId])['result'];

    if (empty($arDeal)) die();

    $sendChatParam = [
      'MESSAGE' => $arDeal['TITLE'].' - Скан-копия Протокола Безопасности получена',
      'DIALOG_ID' => 'chat366',
      'SYSTEM' => 'Y',
    ];
    CMain::getDataHttp('im.message.add', $sendChatParam);

    $notifyParam = [
      'USER_ID' => $arDeal['ASSIGNED_BY_ID'],
      'MESSAGE' => $arDeal['TITLE'].' - Скан-копия Протокола Безопасности получена'
    ];
    CMain::getDataHttp('im.notify.system.add', $notifyParam);
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
}