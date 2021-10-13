<?php

namespace Its\Service;

class CDeal extends CMain
{
  const HL_DEAL_NAME = 'Deal';
  const HL_FILE_NAME = 'File';
  const HL_FILE_FIELD_ENUM_TYPE_XML_ID_SUCCESS = 1;   //Документ принят
  const HL_FILE_FIELD_ENUM_TYPE_XML_ID_REJECT = 2;    //Документ отклонен
  const HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT = 3;   //Документ на рассмотрении
  const ACCREDITATION_DEAL_CATEGORY_ID = 12;
  const INSTALLATION_CENTER_CATEGORY_ID = 2;
  const TS_CATEGORY_ID = 4;


  const ACCREDITATION_DEAL_STAGE_PREPARATION_DOCUMENTS = 'C12:NEW';    //Подготовка документов
  const ACCREDITATION_DEAL_STAGE_CHECK_DOCUMENTS = 'C12:2';    //Документы в проверке
  const ACCREDITATION_DEAL_STAGE_APPROVED = 'C12:1';   //Ваша заявка одобрена
  const ACCREDITATION_DEAL_STAGE_SIGNING_AGREEMENT = 'C12:4';   //Подписание соглашения
  const ACCREDITATION_DEAL_STAGE_END_APPROVED = 'C12:WON';    //Ваша заявка одобрена
  const ACCREDITATION_DEAL_STAGE_FAILED = 'C12:LOSE';   //Сделка провалена
  const ACCREDITATION_DEAL_STAGE_ANALISYS_FAILED = 'C12:APOLOGY';    //Анализ причины провала

  const SYSTEM_ACCREDITATION_DEAL_STATUS_NEW = 'NEW';
  const SYSTEM_ACCREDITATION_DEAL_STATUS_CORRECTION_DOCUMENTS = 'CORRECTION_DOCUMENTS';
  const SYSTEM_ACCREDITATION_DEAL_STATUS_APPROVED = 'APPROVED';
  const SYSTEM_ACCREDITATION_DEAL_STATUS_SIGNING_AGREEMENT = 'SIGNING_AGREEMENT';


  const SUBSIDION_DEAL_STAGE_FILLING_DOCUMENTS = 'C2:NEW';    //Подготовка документов
  const SUBSIDION_DEAL_STAGE_DOCUMENTS_PAYD = 'C2:PREPARATION';    //Документы на возмещении
  const SUBSIDION_DEAL_STAGE_PAYMEND_CONFIRMED = 'C2:6';    //Оплата подтверждена
  const SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW = 'C2:4';    //Документы на проверке
  const SUBSIDION_DEAL_STAGE_DOCS_APPROVED = 'C2:5';    //Документы одобрены

  const SUBSIDION_DEAL_STAGE_END_APPROVED = 'C2:WON';    //Ваша заявка одобрена
  const SUBSIDION_DEAL_STAGE_FAILED = 'C2:LOSE';   //Сделка провалена
  const SUBSIDION_DEAL_STAGE_ANALISYS_FAILED = 'C2:APOLOGY';    //Анализ причины провала

  const SYSTEM_SUBSIDION_DEAL_STATUS_CONTRACT_CONCLUDED = 'CONTRACT_CONCLUDED';   //договор заключен
  const SYSTEM_SUBSIDION_DEAL_STATUS_PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';   //оплата подтверждена
  const SYSTEM_SUBSIDION_DEAL_STATUS_PREPARATION_APP_FOR_PAYMENT = 'PREPARATION_APP_FOR_PAYMENT';   //подготовка заявки на выплату
  const SYSTEM_SUBSIDION_DEAL_STATUS_COMPLETED = 'COMPLETED';   //заявка завершена


  const TS_DEAL_STAGE_GET_DATA = 'C4:NEW';    //Получение данных
  const TS_DEAL_STAGE_REGISTRATION_PTE = 'C4:PREPARATION';    //Оформление ПТЭ (Предварительная Техническая Экспертиза)
  const TS_DEAL_STAGE_PREPARATION_DOCS_GIBDD = 'C4:6';    //Подготовка документов для ГИБДД
  const TS_DEAL_STAGE_DOCS_TRANSFERED_GIBDD = 'C4:FINAL_INVOICE';    //Документы переданы в ГИБДД
  const TS_DEAL_STAGE_ARRIVED_FOR_INSTALLATION = 'C4:5';    //ТС Прибыло на установку
  const TS_DEAL_STAGE_3D_207 = 'C4:EXECUTING';    //ЗД/207
  const TS_DEAL_STAGE_DOCS_IN_CHECK = 'C4:3';    //Документы в проверке
  const TS_DEAL_STAGE_DOCS_IN_CORRECTION = 'C4:7';    //Корректировка документов
  const TS_DEAL_STAGE_ALL_DOCS_APPROVED = 'C4:4';    //Все документы одобрены
  const TS_DEAL_STAGE_SECURITY_PROTOCOL_REGISTRATION = 'C4:PREPAYMENT_INVOICE';   //Оформление ПБ (Протокола Безопасности)
  const TS_DEAL_STAGE_UPLOAD_REGISTRY_SECURITY_PROTOCOL = 'C4:1';    //Выгрузить в Реестр ПБ
  const TS_DEAL_STAGE_SUCCESSFULL = 'C4:WON';    //Сделка успешна
  const TS_DEAL_STAGE_FAILED = 'C4:LOSE';    //Отменена
  const TS_DEAL_STAGE_ANALISYS_FAILED = 'C4:APOLOGY';    //Анализ причины провала

  const SYSTEM_TS_DEAL_STATUS_COLLECTION_DOCS = 'CONTRACT_CONCLUDED';   //Сбор документов
  const SYSTEM_TS_DEAL_STATUS_REGISTRATION_PTE = 'REGISTRATION_PTE';   //Оформление ПТЭ
  const SYSTEM_TS_DEAL_STATUS_SIGNING_GIBDD = 'SIGNING_GIBDD';   //Подписание разрешения в ГИБДД
  const SYSTEM_TS_DEAL_STATUS_RE_EQUIPMENT_TS = 'RE_EQUIPMENT_TS';   //Переоборудование ТС

  const ACCREDITATION_BITRIX24_FILE_LIST = [
    'UF_CRM_1621321681' => 'Устав организации',
    'UF_CRM_1621321880' => 'ИНН',
    'UF_CRM_1575494576' => 'ОГРН (ОГРНИП)',
    'UF_CRM_1616093122972' => 'Реквизиты организации в формате PDF',
    'UF_CRM_1615048968190' => 'Выписка из ЕГРЮЛ ЕГРИП',
    'UF_CRM_1615049008612' => 'Справка из ИФНС об отсутствии неуплаченных налогов',
    'UF_CRM_1620915376' => 'Договор о сотрудничестве с испытательной лабораторией',
    'UF_CRM_1620915489' => 'Договор о сотрудничестве с пунктом по переосвидетельствованию баллонов',
    'UF_CRM_1603137217967' => 'Сертификат ППТО',
    'UF_CRM_1621322721' => 'Договор(а) подтверждающие опыт переоборудования ТС для работы на сжатом природном газе',
    'UF_CRM_1621468277' => 'Акты выполненных работ по переоборудованию',
    'UF_CRM_1620915582' => 'Партнерское соглашение',
    'UF_CRM_1620915637' => 'Документы, подтверждающие квалификацию работников ППТО',
  ];

  const ACCREDITATION_BITRIX24_FILE_LIST_MULTIPLE = [
    'UF_CRM_1621321681',
    'UF_CRM_1575494576',
    'UF_CRM_1620915376',
    'UF_CRM_1620915489',
    'UF_CRM_1621322721',
    'UF_CRM_1621468277',
    'UF_CRM_1620915582',
    'UF_CRM_1620915637',
  ];


  const BITRIX24_MULTIPLE_FILE_FIELDS = [
    'UF_CRM_1621468277',
    'UF_CRM_1621322721'
  ];

  const SUBSIDION_BITRIX24_FILE_LIST = [
    'UF_CRM_1621323021' => 'Заявление о предоставлении Субсидии (приложение №2)',
    'UF_CRM_1621323539' => 'Гарантийное письмо (приложение №3)',
    'UF_CRM_1621323599' => 'Гарантийное письмо (приложение №4)',
    'UF_CRM_1615048968190' => 'Выписка из ЕГРЮЛ ЕГРИП',//?
    'UF_CRM_1621324011' => 'Договор о переоборудовании ТС',
    'UF_CRM_1621468277' => 'Акт выполненных работ',
    'UF_CRM_1621324169' => 'Документ об оплате по переоборудованию ТС',
    'UF_CRM_1621324210' => 'Документ подтверждающий рассрочку (при предоставлении рассрочки)',
    'UF_CRM_1621324410' => 'Отчёт о переоборудовании ТС',
    'UF_CRM_1614175638632' => 'Согласие лизингодателя на внесение изменений в конструкцию ТС (если ТС в лизинге)',
    'UF_CRM_1621325632' => 'Договор аренды ТС (Если ТС в аренде)',
    'UF_CRM_1621325791' => 'Согласие владельца ТС на внесение изменения в конструкцию ТС (Если ТС в аренде)',
    'UF_CRM_1621326260' => 'Спецификация на использованное ГБО',
    'UF_CRM_1623066108712' => 'Соглашение с ООО «Газпром Газомоторное топливо» либо иной организацией (ДЗ - уточнить, пункта не было в списке)',
    'UF_CRM_1621326622' => 'Документ подтверждающий предоставлении скидки',
    'UF_CRM_1621326671' => 'Уведомление ГИБДД о проведении работ по переоборудованию ТС',
    'UF_CRM_1615049008612' => 'Справка из ИФНС об отсутствии задолженности',
    'UF_CRM_1621326555' => 'Справка об отсутствии задолженности в бюджет Республики Татарстан субсидий, бюджетных инвестиций',
    'UF_CRM_1621326718' => 'Справка о том, что участник отбора не находятся в процессе реорганизации',//??
    'UF_CRM_1621327031' => 'Справка о том, что участник отбора не является иностранным юридическим лицом',//??
    'UF_CRM_1621327094' => 'Справка о том, что участник отбора не получает средства из бюджета Республики Татарстан',
    'UF_CRM_1605603557603' => 'Выписка из единого реестра МСП',//??
    'UF_CRM_1621327196' => 'Письменное согласие на публикацию информации об участнике отбора в сети «Интернет»',
  ];

  const TS_BITRIX24_FILE_LIST = [
    'UF_CRM_1614175606698' => 'Копия паспорта (Собственника ТС)',
    'UF_CRM_1575482284' => 'Свидетельство о регистрации ТС',
    'UF_CRM_1621324492' => 'Разрешение ГИБДД на внесение изменений в конструкцию ТС',
    'UF_CRM_1606485425330' => 'Паспорт на баллон',
    'UF_CRM_1621326020' => 'Сертификат на установленное ГБО',
    'UF_CRM_1623074927' => 'Сертификат на баллон',
    'UF_CRM_1621326154' => 'Паспорт двигателя либо договор купли продажи двигателя (при ремотизации)',
    'UF_CRM_1606485376580' => 'Форма 207',
    'UF_CRM_1606485283503' => 'Декларация производителя работ',
    'UF_CRM_1575482164' => 'Паспорт ТС',
  ];

  const INSTALLATION_BITRIX24_FILE_LIST_MULTIPLE = [
    'UF_CRM_1614175606698',
    'UF_CRM_1575482284',
    'UF_CRM_1621324492',
    'UF_CRM_1606485425330',
    'UF_CRM_1621326020',
    'UF_CRM_1606485376580',
    'UF_CRM_1606485283503',
    'UF_CRM_1575482164',
  ];

  const TS_BITRIX24_FILE_LIST_MULTIPLE = [
    'UF_CRM_1614175606698',
    'UF_CRM_1575482284',
    'UF_CRM_1621324492',
    'UF_CRM_1606485425330',
    'UF_CRM_1621326020',
    'UF_CRM_1621326154',
    'UF_CRM_1606485376580',
    'UF_CRM_1606485283503',
    'UF_CRM_1575482164',
  ];

  public static function getStageList(): array
  {
    return [
      self::ACCREDITATION_DEAL_STAGE_PREPARATION_DOCUMENTS,
      self::ACCREDITATION_DEAL_STAGE_APPROVED,
      self::ACCREDITATION_DEAL_STAGE_CHECK_DOCUMENTS,
      self::ACCREDITATION_DEAL_STAGE_SIGNING_AGREEMENT,
      self::ACCREDITATION_DEAL_STAGE_END_APPROVED,
      self::ACCREDITATION_DEAL_STAGE_FAILED,
      self::ACCREDITATION_DEAL_STAGE_ANALISYS_FAILED,
    ];
  }

  public static function getSystemAccreditationStageList(): array
  {
    return [
      self::SYSTEM_ACCREDITATION_DEAL_STATUS_NEW,
      self::SYSTEM_ACCREDITATION_DEAL_STATUS_CORRECTION_DOCUMENTS,
      self::SYSTEM_ACCREDITATION_DEAL_STATUS_APPROVED,
      self::SYSTEM_ACCREDITATION_DEAL_STATUS_SIGNING_AGREEMENT
    ];
  }

  public static function getSystemAccreditationStatus($statusDeal): string
  {
    $systemStatus = '';
    if ($statusDeal == self::ACCREDITATION_DEAL_STAGE_CHECK_DOCUMENTS) {
      $systemStatus = self::SYSTEM_ACCREDITATION_DEAL_STATUS_NEW;
    } elseif ($statusDeal == self::ACCREDITATION_DEAL_STAGE_SIGNING_AGREEMENT) {
      $systemStatus = self::SYSTEM_ACCREDITATION_DEAL_STATUS_SIGNING_AGREEMENT;
    }

    return $systemStatus;
  }

  public static function getSystemSubsidionStatus($statusDeal): string
  {
    $systemStatus = '';
    switch ($statusDeal) {
      case self::SUBSIDION_DEAL_STAGE_DOCUMENTS_PAYD:
        $systemStatus = self::SYSTEM_SUBSIDION_DEAL_STATUS_CONTRACT_CONCLUDED;
        break;
      case self::SUBSIDION_DEAL_STAGE_PAYMEND_CONFIRMED:
        $systemStatus = self::SYSTEM_SUBSIDION_DEAL_STATUS_PAYMENT_CONFIRMED;
        break;
      case self::SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW:
        $systemStatus = self::SYSTEM_SUBSIDION_DEAL_STATUS_PREPARATION_APP_FOR_PAYMENT;
        break;
      case self::SUBSIDION_DEAL_STAGE_DOCS_APPROVED:
      case self::SUBSIDION_DEAL_STAGE_END_APPROVED:
      case self::SUBSIDION_DEAL_STAGE_FAILED:
      case self::SUBSIDION_DEAL_STAGE_ANALISYS_FAILED:
        $systemStatus = self::SYSTEM_SUBSIDION_DEAL_STATUS_COMPLETED;
        break;
    }

    return $systemStatus;
  }

  public static function getSystemSubsidionStageList(): array
  {
    return [
      self::SYSTEM_SUBSIDION_DEAL_STATUS_CONTRACT_CONCLUDED,
      self::SYSTEM_SUBSIDION_DEAL_STATUS_PAYMENT_CONFIRMED,
      self::SYSTEM_SUBSIDION_DEAL_STATUS_PREPARATION_APP_FOR_PAYMENT,
      self::SYSTEM_SUBSIDION_DEAL_STATUS_COMPLETED
    ];
  }

  public static function getSystemTSStatus($statusDeal): string
  {
    $systemStatus = '';
    switch ($statusDeal) {
      case self::TS_DEAL_STAGE_GET_DATA:
        $systemStatus = self::SYSTEM_TS_DEAL_STATUS_COLLECTION_DOCS;
        break;
      case self::TS_DEAL_STAGE_REGISTRATION_PTE:
      case self::TS_DEAL_STAGE_PREPARATION_DOCS_GIBDD:
        $systemStatus = self::SYSTEM_TS_DEAL_STATUS_REGISTRATION_PTE;
        break;
      case self::TS_DEAL_STAGE_DOCS_TRANSFERED_GIBDD:
      case self::TS_DEAL_STAGE_ARRIVED_FOR_INSTALLATION:
        $systemStatus = self::SYSTEM_TS_DEAL_STATUS_SIGNING_GIBDD;
        break;
      case self::TS_DEAL_STAGE_3D_207:
      case self::TS_DEAL_STAGE_DOCS_IN_CHECK:
      case self::TS_DEAL_STAGE_SECURITY_PROTOCOL_REGISTRATION:
      case self::TS_DEAL_STAGE_UPLOAD_REGISTRY_SECURITY_PROTOCOL:
      case self::TS_DEAL_STAGE_SUCCESSFULL:
      case self::TS_DEAL_STAGE_FAILED:
      case self::TS_DEAL_STAGE_ANALISYS_FAILED:
        $systemStatus = self::SYSTEM_TS_DEAL_STATUS_RE_EQUIPMENT_TS;
        break;
    }

    return $systemStatus;
  }

  public static function getSystemTsStageList(): array
  {
    return [
      self::SYSTEM_TS_DEAL_STATUS_COLLECTION_DOCS,
      self::SYSTEM_TS_DEAL_STATUS_REGISTRATION_PTE,
      self::SYSTEM_TS_DEAL_STATUS_SIGNING_GIBDD,
      self::SYSTEM_TS_DEAL_STATUS_RE_EQUIPMENT_TS
    ];
  }

  public static function getCountDealRequiringAttention(): int
  {
    global $USER;
    $arUserInfo = CMain::getUserInfo($USER->GetID());

    $regionId = $arUserInfo['REGION']['ID'];

    $fileTypeList = CMain::getEnumField(25);

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);

    if($arUserInfo['IS_PRE_APPROVED']) {
      $arFilter = [
        'UF_STAGE_ID' => CDeal::SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW,
        'UF_CATEGORY_ID' => CDeal::INSTALLATION_CENTER_CATEGORY_ID,
        'COMPANY_REGION_ID' => $regionId,
        'COMPANY_ACCREDITED' => true,
        'COMPANY_TYPE' => CCompany::FIELD_COMPANY_TYPE_ID,
        '>FILE_SIGNED' => 0,
        '!UF_COMPANY_ID' => [4110, 4168],
        'UF_PACK_DOCS_CONFIRMED' => true,
        [
          'LOGIC' => 'OR',
          ['UF_PRE_APPROVED' => false],
          ['UF_PRE_APPROVED' => true, 'UF_POST_APPROVED' => true],
        ],
      ];
    } else {
      $arFilter = [
        'UF_STAGE_ID' => CDeal::SUBSIDION_DEAL_STAGE_DOCS_UNDER_REVIEW,
        'UF_CATEGORY_ID' => CDeal::INSTALLATION_CENTER_CATEGORY_ID,
        'UF_PRE_APPROVED' => true,
        'UF_POST_APPROVED' => false,
        'COMPANY_REGION_ID' => $regionId,
        'COMPANY_ACCREDITED' => true,
        'COMPANY_TYPE' => CCompany::FIELD_COMPANY_TYPE_ID,
        '>FILE_SIGNED' => 0,
        'UF_PACK_DOCS_CONFIRMED' => true,
      ];
    }

    $rsDeal = $hlDealClassName::getlist([
      'order' => ['ID' => 'desc'],
      'select' => [
        'ID',
        'UF_NAME',
        'UF_PACK_DOCS_CONFIRMED',
        'UF_COMPANY_ID',
        'UF_POST_APPROVED',
        'COMPANY_REGION_ID' => 'COMPANY.UF_REGION',
        'COMPANY_ACCREDITED' => 'COMPANY.UF_ACCREDITED',
        'COMPANY_TYPE' => 'COMPANY.UF_COMPANY_TYPE',
        'FILE_ID' => 'FILES.ID',
        'FILE_STATUS' => 'FILES.UF_STATUS',
        'FILE_IS_AGREEMENT' => 'FILES.UF_IS_AGREEMENT',
        'FILE_IS_ACT' => 'FILES.UF_IS_ACT',
        'FILE_NAME' => 'FILES.UF_NAME',
        'FILE_SIGNED' => 'FILES.UF_FILE_SIGNED',
        'FILE_IS_CET_CONCLUSION' => 'FILES.UF_IS_CET_CONCLUSION',
      ],
      'filter' => $arFilter,
      'runtime' => [
        'COMPANY' => [
          'data_type' => CMain::getHlEntity(CCompany::HL_COMPANY_NAME),
          'reference' => [
            '=this.UF_COMPANY_ID' => 'ref.ID',
          ],
        ],
        'FILES' => [
          'data_type' => CMain::getHlEntity(CDeal::HL_FILE_NAME),
          'reference' => [
            '=this.ID' => 'ref.UF_DEAL_ID',
          ],
          'join_type' => 'inner',
        ]
      ],
    ]);

    $countDeal = 0;
    $arDeal = [];
    while ($arResultDeal = $rsDeal->fetch()) {
      $arDeal[$arResultDeal['ID']][] = $arResultDeal;
    }

    $arDealIds = [];
    foreach ($arDeal as $dealId => $arItem) {
      foreach ($arItem as $arVal) {
        if ($arVal['FILE_STATUS'] == $fileTypeList[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'] && in_array($dealId, $arDealIds) === false) {
            $countDeal++;
            $arDealIds[] = $dealId;
        }
      }
    }

    return $countDeal;
  }
}